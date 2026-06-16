<?php

session_start();

require_once __DIR__ . '/../config/db.php';

// Simpele controle voor dag 2
if (!isset($_SESSION['can_download']) || $_SESSION['can_download'] !== true) {
    die('Je mag dit bestand niet downloaden.');
}

// Controleer of id bestaat
if (!isset($_GET['id'])) {
    die('Geen bestand gekozen.');
}

$fileId = $_GET['id'];

// Bestand zoeken in database
$sql = "SELECT * FROM files WHERE id = :id AND can_download = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':id' => $fileId
]);

$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    die('Bestand niet gevonden.');
}

$filePath = $file['file_path'];

// Controleer of bestand echt bestaat
if (!file_exists($filePath)) {
    die('Bestand staat niet meer op de server.');
}

// MD5 opnieuw controleren
$currentHash = md5_file($filePath);

if ($currentHash !== $file['md5_hash']) {
    die('Bestand is veranderd. Download gestopt.');
}

// Bestand downloaden
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;