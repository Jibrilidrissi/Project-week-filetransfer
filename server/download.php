<?php
// Database verbinding via config
require_once __DIR__ . '/../config/db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // Zoek het bestand op in de database
        $stmt = $conn->prepare("SELECT * FROM files WHERE id = ?");
        $stmt->execute([$id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($file) {
            $filepath = __DIR__ . '/../uploads/' . $file['data'];
            
            // Controleer of het bestand daadwerkelijk bestaat op de server
            if (file_exists($filepath)) {
                // Stuur de benodigde headers voor een veilige bestandsoverdracht
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($file['name']) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filepath));
                
                // Buffer opschonen en het bestand streamen
                ob_clean();
                flush();
                readfile($filepath);
                exit;
            }
        }
    } catch (PDOException $e) {
        // Stille foutafhandeling
    }
}

// Als het bestand of de ID niet bestaat
http_response_code(404);
echo "Bestand niet gevonden of is niet meer beschikbaar.";
exit;
