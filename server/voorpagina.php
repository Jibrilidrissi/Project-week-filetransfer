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
            $melding = "Dit bestandstype (." . htmlspecialchars($ext) . ") is niet ondersteund.";
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
    <title>Secure Transfer Vault - FileTransfer</title>
    <link rel="stylesheet" href="../client/style.css">
</head>
<body>

    <header class="header">
        <div class="header__container">
            <div class="header__logo-container">
                <a href="voorpagina.php" class="header__logo">
                    <svg class="header__logo-icon" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <span>FileTransfer</span>
                </a>
            </div>
            <div class="header__user">
                <span>Logged in as: <strong><?php echo htmlspecialchars($_SESSION["email"]); ?></strong></span>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php" class="btn btn--admin-panel">⚙ Admin Panel</a>
                <?php endif; ?>
                <a href="../client/index.php?action=logout" class="btn btn--logout">Sign Out</a>
            </div>
        </div>
    </header>

    <main class="container">
        
        <!-- User Alerts -->
        <?php if ($melding): ?>
            <div class="alert alert--<?php echo $meldingType; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <?php if ($meldingType === 'success'): ?>
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 8 8 12 12 16"></polyline>
                        <line x1="16" y1="12" x2="8" y2="12"></line>
                    <?php else: ?>
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    <?php endif; ?>
                </svg>
                <span><?php echo $melding; ?></span>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Upload section -->
            <section class="upload-section">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    Secure Upload
                </h2>
                <p class="upload-section__subtitle">Encrypt and store files in the storage environment</p>
                
                <form action="voorpagina.php" method="POST" enctype="multipart/form-data" class="upload-form">
                    
                    <!-- State 1: Drop Zone Only -->
                    <div class="upload-start-state">
                        <div class="drag-drop-zone">
                            <div class="drag-drop-zone__plus-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </div>
                            <div class="drag-drop-zone__text">Click to add your files</div>
                            <div class="drag-drop-zone__subtext">or drag them here</div>
                            <input type="file" name="bestand" id="bestand" required class="drag-drop-zone__input">
                        </div>
                        <div class="upload-terms">
                            Max. file size: <strong><?php echo (MAX_FILE_SIZE / (1024 * 1024)); ?> MB</strong> | Allowed: <strong><?php echo implode(', ', ALLOWED_EXTENSIONS); ?></strong>
                        </div>
                    </div>

                    <!-- State 2: Dynamic Split Details Layout -->
                    <div class="upload-split-layout" style="display: none;">
                        
                        <!-- Left Panel: File Listing & Reset -->
                        <div class="upload-left-panel">
                            <div class="file-list-header">1 uploaded file</div>
                            <div class="file-list-container">
                                <div class="file-item">
                                    <svg class="file-item__icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                    </svg>
                                    <div class="file-item__details">
                                        <div class="file-item__name">file.zip</div>
                                        <div class="file-item__size">0.0 KB</div>
                                    </div>
                                    <button type="button" class="file-item__clear" title="Remove selection">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="button" class="add-more-btn" onclick="document.getElementById('bestand').click()" title="Change Selected File">
                                <div class="add-more-btn__plus">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </div>
                                <span>Change file</span>
                            </button>
                        </div>

                        <!-- Right Panel: Metadata Form & Submit -->
                        <div class="upload-right-panel">
                            <div class="form-group">
                                <label for="beschrijving" class="form-label">Description (Optional)</label>
                                <input type="text" name="beschrijving" id="beschrijving" placeholder="Add a description or note for reference" class="form-control">
                            </div>

                            <div class="form-group">
                                <label for="file_password" class="form-label">Security Key / Password (Required)</label>
                                <input type="password" name="file_password" id="file_password" required placeholder="Choose protection password" class="form-control">
                            </div>
                            
                            <button type="submit" class="btn btn--primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                Transmit Encrypted File
                            </button>

                            <!-- Progress Container inside right panel -->
                            <div class="upload-progress-container">
                                <div class="upload-progress-header">
                                    <span class="upload-progress-title">Uplink progress</span>
                                    <span class="upload-progress-percent">0%</span>
                                </div>
                                <div class="upload-progress-track">
                                    <div class="upload-progress-bar"></div>
                                </div>
                                <div class="upload-progress-status">
                                    <span id="progress-status-text">Preparing...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="secure-badge-container">
                    <span class="secure-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        AES-256 Link Protection
                    </span>
                    <span class="secure-badge secure-badge--audit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Private Key Protocol
                    </span>
                </div>
            </section>

            <!-- Download section -->
            <section class="upload-section">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--success);"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Secure Download
                </h2>
                <p class="upload-section__subtitle">Decrypt and retrieve assets with unique identifiers</p>
                
                <?php if (!empty($downloadMelding)): ?>
                    <div class="alert alert--error" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <span><?php echo htmlspecialchars($downloadMelding); ?></span>
                    </div>
                <?php endif; ?>
                
                <form action="voorpagina.php" method="POST" class="download-form">
                    <div class="form-group">
                        <label for="download_file_id" class="form-label">Asset File ID</label>
                        <input type="number" name="download_file_id" id="download_file_id" required placeholder="e.g. 104" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="download_password" class="form-label">Vault Password / Key</label>
                        <input type="password" name="download_password" id="download_password" required placeholder="Enter matching access key" class="form-control">
                    </div>
                    
                    <button type="submit" name="download_file_by_id" class="btn btn--primary" style="background-color: var(--success); box-shadow: 0 4px 14px var(--success-glow);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Decrypt & Download
                    </button>
                </form>

                <div class="secure-badge-container">
                    <span class="secure-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        Integrity Verified
                    </span>
                    <span class="secure-badge secure-badge--audit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        Strict Sharing Policy
                    </span>
                </div>
            </section>
        </div>

        <!-- Overzicht van geüploade bestanden -->
        <section class="files-section">
            <div class="files-section__header">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Secure Transfer History
                </h2>
                <span class="files-section__count"><?php echo count($bestanden); ?> uploaded</span>
            </div>
            
            <?php if (empty($bestanden)): ?>
                <p class="no-files">Your private archive is currently empty. Upload files above to view transfer records.</p>
            <?php else: ?>
                <div class="files-table-wrapper">
                    <table class="files-table">
                        <thead>
                            <tr>
                                <th>File ID</th>
                                <th>Filename</th>
                                <th>Description</th>
                                <th>Uploaded On</th>
                                <th>Security Passkey</th>
                                <th>Uplink Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bestanden as $bestand): ?>
                                <tr>
                                    <td>
                                        <div class="file-id-cell">
                                            <code>#<?php echo (int)$bestand["id"]; ?></code>
                                            <button class="copy-btn" onclick="copyToClipboard('<?php echo (int)$bestand['id']; ?>', this)" title="Copy File ID">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td style="font-weight: 500;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                            <?php echo htmlspecialchars($bestand["name"]); ?>
                                        </div>
                                    </td>
                                    <td style="color: var(--text-muted);"><?php echo htmlspecialchars($bestand["beschrijving"] ?: 'No description provided'); ?></td>
                                    <td><?php echo htmlspecialchars(date("d M Y", strtotime($bestand["uploaded_date"]))); ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.50rem;">
                                            <span class="spoiler" onclick="this.classList.toggle('spoiler--revealed')" title="Click to show password">
                                                <?php echo htmlspecialchars($bestand["password"] ?: '—'); ?>
                                            </span>
                                            <?php if ($bestand["password"]): ?>
                                                <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($bestand['password']); ?>', this)" title="Copy Security Passkey">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-pill status-pill--success">Encrypted</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <script>
        const ALLOWED_EXTENSIONS = <?php echo json_encode(ALLOWED_EXTENSIONS); ?>;
        const MAX_FILE_SIZE = <?php echo MAX_FILE_SIZE; ?>;
    </script>
    <script src="../client/script.js"></script>
</body>
</html>