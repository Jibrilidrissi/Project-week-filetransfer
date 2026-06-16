<?php
session_start();

// Sessie-controle: als de gebruiker niet is ingelogd, stuur hem terug naar index.php
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

// Database verbinding parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "filetransfer";

try {
    // Verbinding maken met de database via PDO
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Verbinding mislukt: " . $e->getMessage());
}

$melding = "";
$meldingType = ""; // "success" of "error"

// Vaste uploadmap definiëren en aanmaken als deze nog niet bestaat
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Bestand upload verwerken
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["bestand"])) {
    $file = $_FILES["bestand"];
    $beschrijving = trim($_POST["beschrijving"] ?? '');

    // Controleer of er geen fouten waren tijdens de upload
    if ($file["error"] === UPLOAD_ERR_OK) {
        $originalName = basename($file["name"]);
        
        // Genereer een unieke bestandsnaam om overschrijven te voorkomen
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $uniqueName = $baseName . "_" . time() . ($ext ? "." . $ext : "");
        
        $targetPath = $uploadDir . $uniqueName;

        // Verplaats het bestand van de tijdelijke locatie naar de uploadmap
        if (move_uploaded_file($file["tmp_name"], $targetPath)) {
            try {
                // Sla de gegevens op in de database tabel 'files' met het user_id van de ingelogde gebruiker
                $stmt = $conn->prepare("INSERT INTO files (name, beschrijving, data, uploaded_date, user_id) VALUES (?, ?, ?, NOW(), ?)");
                $stmt->execute([$originalName, $beschrijving, $uniqueName, $_SESSION["user_id"]]);
                
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
    <link rel="stylesheet" href="style/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <header class="header">
        <div class="header__container">
            <h1 class="header__logo">FileTransfer</h1>
            <div class="header__user">
                <span>Ingelogd als: <strong><?php echo htmlspecialchars($_SESSION["email"]); ?></strong></span>
                <a href="index.php?action=logout" class="btn btn--logout">Uitloggen</a>
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

        <!-- Stap 2: HTML-formulier waarmee een bestand gekozen kan worden -->
        <section class="upload-section">
            <h2>Bestand Uploaden</h2>
            <form action="voorpagina.php" method="POST" enctype="multipart/form-data" class="upload-form">
                <div class="form-group">
                    <label for="bestand" class="form-label">Kies een bestand:</label>
                    <input type="file" name="bestand" id="bestand" required class="form-control-file">
                </div>
                
                <div class="form-group">
                    <label for="beschrijving" class="form-label">Beschrijving (optioneel):</label>
                    <input type="text" name="beschrijving" id="beschrijving" placeholder="Voer een korte beschrijving in" class="form-control">
                </div>
                
                <button type="submit" class="btn btn--primary">Bestand Versturen</button>
            </form>
        </section>

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
                                <th>Bestandsnaam</th>
                                <th>Beschrijving</th>
                                <th>Download</th>
                                <th>Deellink</th>
                                <th>Geüpload op</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bestanden as $bestand): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($bestand["name"]); ?></td>
                                    <td><?php echo htmlspecialchars($bestand["beschrijving"] ?: '-'); ?></td>
                                    <td>
                                        <a href="uploads/<?php echo urlencode($bestand["data"]); ?>" download="<?php echo htmlspecialchars($bestand["name"]); ?>" class="btn btn--download">
                                            Download
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $dir = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
                                        $shareUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . ($dir === '/' ? '' : $dir) . "/download.php?id=" . $bestand['id'];
                                        ?>
                                        <div class="share-container">
                                            <input type="text" readonly value="<?php echo htmlspecialchars($shareUrl); ?>" class="form-control share-input" onclick="this.select();">
                                            <button class="btn btn--share" onclick="copyToClipboard('<?php echo htmlspecialchars($shareUrl); ?>', this)">Kopieer</button>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars(date("Y-m-d", strtotime($bestand["uploaded_date"]))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <script>
    function copyToClipboard(text, button) {
        navigator.clipboard.writeText(text).then(function() {
            const originalText = button.textContent;
            button.textContent = 'Gekopieerd!';
            button.style.backgroundColor = '#10b981';
            button.style.color = '#ffffff';
            setTimeout(function() {
                button.textContent = originalText;
                button.style.backgroundColor = '';
                button.style.color = '';
            }, 2000);
        }, function(err) {
            alert('Fout bij kopiëren: ' + err);
        });
    }
    </script>

</body>
</html>