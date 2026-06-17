<?php

require_once __DIR__ . '/../config/security.php';
requireSecureConnection();
require_once __DIR__ . '/../config/auth.php';
requireLogin();

require_once __DIR__ . '/../config/db.php';


// Controleer of id bestaat
if (!isset($_GET['id'])) {
    die('Geen bestand gekozen.');
}

$fileId = $_GET['id'];

// Bestand zoeken in database
if (isAdmin()) {
    $sql = "SELECT * FROM files WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $fileId
    ]);
} else {
    $sql = "SELECT * FROM files 
            WHERE id = :id 
            AND (can_download = 1 OR uploaded_by = :user_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $fileId,
        ':user_id' => $_SESSION['user_id']
    ]);
}

$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    die('Bestand niet gevonden of geen toegang..');
}

$filePath = $file['file_path'];

// Controleer of bestand echt bestaat
if (!file_exists($filePath)) {
    die('Bestand staat niet meer op de server.');
}

// SHA-256 opnieuw controleren
$currentHash = hash_file('sha256', $filePath);

if ($currentHash !== $file['file_hash']) {
    die('Bestand is veranderd. Download gestopt.');
}

// Bestand downloaden
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;