<?php
session_start();

// Database verbinding via config
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/download_log.php';

if (isset($_GET['file_id'])) {
    $fileId = trim($_GET['file_id']);
    
    try {
        // Zoek het bestand op via het 5-cijferige file_id
        $stmt = $conn->prepare("SELECT * FROM files WHERE file_id = ?");
        $stmt->execute([$fileId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($file) {
            $filepath = __DIR__ . '/../uploads/' . $file['data'];
            
            // Controleer of het bestand daadwerkelijk bestaat op de server
            if (file_exists($filepath)) {
                // Maak opnieuw een SHA-256 hash van het bestand
                $currentHash = hash_file("sha256", $filepath);

                // Controleer of de hash nog hetzelfde is als in de database
                if ($currentHash !== $file["file_hash"]) {
                    http_response_code(400);
                    echo "Bestand is aangepast. Download gestopt.";
                    exit;
                }

                $downloaderEmail = $_SESSION['email'] ?? ('Anoniem (' . ($_SERVER['REMOTE_ADDR'] ?? 'onbekend') . ')');
                logDownload(
                    $conn,
                    $file,
                    isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
                    $downloaderEmail
                );

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
