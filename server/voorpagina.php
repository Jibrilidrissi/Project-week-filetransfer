<?php
session_start();

// Sessie-controle: als de gebruiker niet is ingelogd, stuur hem terug naar index.php in client
if (!isset($_SESSION["user_id"])) {
    header("Location: ../client/index.php");
    exit();
}

// Database verbinding via config
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';

$melding = "";
$meldingType = ""; // "success" of "error"
$downloadMelding = "";

// Download bestand via ID en Wachtwoord verwerken
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["download_file_by_id"])) {
    $downloadId = (int)($_POST["download_file_id"] ?? 0);
    $downloadPassword = $_POST["download_password"] ?? '';
    
    if ($downloadId <= 0 || empty($downloadPassword)) {
        $downloadMelding = "Vul a.u.b. zowel het bestand ID als het wachtwoord in.";
    } else {
        try {
            // Zoek het bestand op in de database
            $stmt = $conn->prepare("SELECT * FROM files WHERE id = ?");
            $stmt->execute([$downloadId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($file) {
                if ($file['password'] === $downloadPassword || password_verify($downloadPassword, $file['password'])) {
                    $filepath = __DIR__ . '/../uploads/' . $file['data'];
                    
                    if (file_exists($filepath)) {
                        // Maak opnieuw een SHA-256 hash van het bestand
                        $currentHash = hash_file("sha256", $filepath);

                        // Controleer of de hash nog hetzelfde is als in de database
                        if ($file["file_hash"] !== null && $currentHash !== $file["file_hash"]) {
                            $downloadMelding = "Bestand is aangepast. Download gestopt.";
                        } else {
                            header('Content-Description: File Transfer');
                            header('Content-Type: application/octet-stream');
                            header('Content-Disposition: attachment; filename="' . basename($file['name']) . '"');
                            header('Expires: 0');
                            header('Cache-Control: must-revalidate');
                            header('Pragma: public');
                            header('Content-Length: ' . filesize($filepath));
                            
                            ob_clean();
                            flush();
                            readfile($filepath);
                            exit();
                        }
                    } else {
                        $downloadMelding = "Bestand bestaat niet fysiek op de server.";
                    }
                } else {
                    $downloadMelding = "Onjuist wachtwoord voor dit bestand.";
                }
            } else {
                $downloadMelding = "Geen bestand gevonden met dit ID.";
            }
        } catch (PDOException $e) {
            $downloadMelding = "Fout bij het ophalen van het bestand: " . $e->getMessage();
        }
    }
}

// Vaste uploadmap definiëren (in de root) en aanmaken als deze nog niet bestaat
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Bestand upload verwerken
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["bestand"])) {
    $file = $_FILES["bestand"];
    $beschrijving = trim($_POST["beschrijving"] ?? '');
    $filePassword = $_POST["file_password"] ?? '';

    // Controleer of het wachtwoord is ingevuld
    if (empty($filePassword)) {
        $melding = "Een wachtwoord is verplicht om dit bestand te uploaden.";
        $meldingType = "error";
    }
    // Controleer of er geen fouten waren tijdens de upload
    elseif ($file["error"] === UPLOAD_ERR_OK) {
        $originalName = basename($file["name"]);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        
        // CONTROLES:
        // 1. Controleer bestandstype
        if (!in_array($ext, ALLOWED_EXTENSIONS)) {
            $melding = "Dit bestandstype (." . htmlspecialchars($ext) . ") is niet toegestaan.";
            $meldingType = "error";
        }
        // 2. Controleer maximale bestandsgrootte
        elseif ($file["size"] > MAX_FILE_SIZE) {
            $melding = "Het bestand is te groot. Maximale grootte is " . (MAX_FILE_SIZE / (1024 * 1024)) . " MB.";
            $meldingType = "error";
        }
        else {
            // Unieke naam genereren
            $uniqueName = $baseName . "_" . time() . ($ext ? "." . $ext : "");
            $targetPath = $uploadDir . $uniqueName;

            // Verplaats het bestand van de tijdelijke locatie naar de uploadmap
            if (move_uploaded_file($file["tmp_name"], $targetPath)) {

            // Maak een SHA-256 hash van het opgeslagen bestand
            $fileHash = hash_file("sha256", $targetPath);
                try {
                    // Sla de gegevens op in de database tabel 'files' met het user_id van de ingelogde gebruiker (inclusief wachtwoord en SHA-256 hash)
                    $stmt = $conn->prepare("INSERT INTO files (name, beschrijving, data, uploaded_date, password, file_hash, user_id) VALUES (?, ?, ?, NOW(), ?, ?, ?)");
                    $stmt->execute([$originalName, $beschrijving, $uniqueName, $filePassword, $fileHash, $_SESSION["user_id"]]);
                    
                    $melding = "Bestand '" . htmlspecialchars($originalName) . "' is succesvol geüpload!";
                    $meldingType = "success";
                } catch (PDOException $e) {
                    $melding = "Fout bij het opslaan in de database: " . $e->getMessage();
                    $meldingType = "error";
                }
            } else {
                $melding = "Er is een fout opgetreden bij het opslaan van het bestand op de server.";
                $meldingType = "error";
            }
        }
    } else {
        // Bepaal de foutmelding op basis van de PHP upload error code
        switch ($file["error"]) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $melding = "Het geüploade bestand is te groot.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $melding = "Kies eerst een bestand om te uploaden.";
                break;
            default:
                $melding = "Er is iets misgegaan tijdens het uploaden. Foutcode: " . $file["error"];
                break;
        }
        $meldingType = "error";
    }
}

// Haal alleen de geüploade bestanden van de ingelogde gebruiker op om te tonen op de pagina
try {
    $stmt = $conn->prepare("SELECT * FROM files WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$_SESSION["user_id"]]);
    $bestanden = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $bestanden = [];
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Voorpagina - Bestandsupload</title>
    <link rel="stylesheet" href="../client/style.css">
</head>
<body>

    <header class="header">
        <div class="header__container">
            <h1 class="header__logo">FileTransfer</h1>
            <div class="header__user">
                <span>Ingelogd als: <strong><?php echo htmlspecialchars($_SESSION["email"]); ?></strong></span>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php" class="btn btn--admin-panel">⚙ Admin Paneel</a>
                <?php endif; ?>
                <a href="../client/index.php?action=logout" class="btn btn--logout">Uitloggen</a>
            </div>
        </div>
    </header>

    <main class="container">
        
        <!-- Melding aan de gebruiker tonen -->
        <?php if ($melding): ?>
            <div class="alert alert--<?php echo $meldingType; ?>">
                <?php echo $melding; ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Stap 2: HTML-formulier waarmee een bestand gekozen kan worden -->
            <section class="upload-section" style="margin-bottom: 0;">
                <h2>Bestand Uploaden</h2>
                <form action="voorpagina.php" method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group">
                        <label for="bestand" class="form-label">Kies een bestand:</label>
                        <input type="file" name="bestand" id="bestand" required class="form-control-file">
                        <p class="form-help-text">
                            Max. bestandsgrootte: <strong><?php echo (MAX_FILE_SIZE / (1024 * 1024)); ?> MB</strong><br>
                            Toegestane bestandstypes: <strong><?php echo implode(', ', ALLOWED_EXTENSIONS); ?></strong>
                        </p>
                    </div>
                    
                    <div class="form-group">
                        <label for="beschrijving" class="form-label">Beschrijving (optioneel):</label>
                        <input type="text" name="beschrijving" id="beschrijving" placeholder="Voer een korte beschrijving in" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="file_password" class="form-label">Wachtwoord (verplicht):</label>
                        <input type="password" name="file_password" id="file_password" required placeholder="Voer een wachtwoord in" class="form-control">
                    </div>
                    
                    <button type="submit" class="btn btn--primary">Bestand Versturen</button>
                </form>
            </section>

            <!-- Stap 3: Bestand downloaden via ID & Wachtwoord -->
            <section class="upload-section" style="margin-bottom: 0;">
                <h2>Bestand Downloaden via ID</h2>
                <?php if (!empty($downloadMelding)): ?>
                    <div class="alert alert--error" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem;">
                        <?php echo htmlspecialchars($downloadMelding); ?>
                    </div>
                <?php endif; ?>
                <form action="voorpagina.php" method="POST" class="download-form">
                    <div class="form-group">
                        <label for="download_file_id" class="form-label">Bestand ID:</label>
                        <input type="number" name="download_file_id" id="download_file_id" required placeholder="Voer het bestand ID in" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="download_password" class="form-label">Wachtwoord:</label>
                        <input type="password" name="download_password" id="download_password" required placeholder="Voer het bestandswachtwoord in" class="form-control">
                    </div>
                    
                    <button type="submit" name="download_file_by_id" class="btn btn--primary">Downloaden</button>
                </form>
            </section>
        </div>

        <!-- Overzicht van geüploade bestanden -->
        <section class="files-section">
            <h2>Geüploade Bestanden</h2>
            <?php if (empty($bestanden)): ?>
                <p class="no-files">Er zijn nog geen bestanden geüpload.</p>
            <?php else: ?>
                <div class="files-table-wrapper">
                    <table class="files-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Bestandsnaam</th>
                                <th>Beschrijving</th>
                                <th>Geüpload op</th>
                                <th>Wachtwoord</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bestanden as $bestand): ?>
                                <tr>
                                    <td><?php echo (int)$bestand["id"]; ?></td>
                                    <td><?php echo htmlspecialchars($bestand["name"]); ?></td>
                                    <td><?php echo htmlspecialchars($bestand["beschrijving"] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars(date("Y-m-d", strtotime($bestand["uploaded_date"]))); ?></td>
                                    <td>
                                        <span class="spoiler" onclick="this.classList.toggle('spoiler--revealed')">
                                            <?php echo htmlspecialchars($bestand["password"] ?: '—'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </main>

</body>
</html>