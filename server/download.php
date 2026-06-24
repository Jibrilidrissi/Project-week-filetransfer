<?php
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/download_log.php';
require_once __DIR__ . '/../config/file_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Directe downloads zijn geblokkeerd. Gebruik het downloadformulier met file ID en wachtwoord.';
    exit;
}

$fileId = trim($_POST['file_id'] ?? '');
$password = $_POST['download_password'] ?? '';

if ($fileId === '' || $password === '') {
    http_response_code(400);
    echo 'File ID en wachtwoord zijn verplicht.';
    exit;
}

try {
    $stmt = $conn->prepare('SELECT * FROM files WHERE file_id = ?');
    $stmt->execute([$fileId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        http_response_code(404);
        echo 'Bestand niet gevonden of is niet meer beschikbaar.';
        exit;
    }

    $downloadError = streamSecureDownload(
        $file,
        $password,
        __DIR__ . '/../uploads/',
        function (array $downloadedFile) use ($conn): void {
            $downloaderEmail = $_SESSION['email'] ?? ('Anoniem (' . ($_SERVER['REMOTE_ADDR'] ?? 'onbekend') . ')');
            logDownload(
                $conn,
                $downloadedFile,
                isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
                $downloaderEmail
            );
        }
    );
    if ($downloadError !== null) {
        http_response_code(403);
        echo $downloadError;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Fout bij het ophalen van het bestand.';
}
