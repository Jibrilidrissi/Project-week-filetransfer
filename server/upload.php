<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Controleer of er een bestand is gekozen
if (!isset($_FILES['file'])) {
    header('Location: ../client/index.php?message=Geen bestand gekozen&type=error');
    exit;
}

$file = $_FILES['file'];

// Controleer of upload gelukt is
if ($file['error'] !== UPLOAD_ERR_OK) {
    header('Location: ../client/index.php?message=Upload mislukt&type=error');
    exit;
}

// Controleer bestandsgrootte
if ($file['size'] > MAX_FILE_SIZE) {
    header('Location: ../client/index.php?message=Bestand is te groot&type=error');
    exit;
}

// Bestandsextensie ophalen
$originalName = $file['name'];
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

// Controleer bestandstype
if (!in_array($extension, $allowedExtensions)) {
    header('Location: ../client/index.php?message=Bestandstype niet toegestaan&type=error');
    exit;
}

// Veilige naam maken
$nameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nameWithoutExtension);

$storedName = time() . '_' . $safeName . '.' . $extension;
$filePath = UPLOAD_DIR . $storedName;

// Uploadmap maken als die niet bestaat
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Bestand opslaan
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    header('Location: ../client/index.php?message=Bestand opslaan mislukt&type=error');
    exit;
}

// MD5 hash maken voor simpele controle
$md5Hash = md5_file($filePath);

// Bestandinformatie opslaan in database
$sql = "INSERT INTO files 
        (original_name, stored_name, file_path, file_type, file_size, md5_hash)
        VALUES 
        (:original_name, :stored_name, :file_path, :file_type, :file_size, :md5_hash)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':original_name' => $originalName,
    ':stored_name' => $storedName,
    ':file_path' => $filePath,
    ':file_type' => $extension,
    ':file_size' => $file['size'],
    ':md5_hash' => $md5Hash
]);

header('Location: ../client/index.php?message=Bestand succesvol geüpload&type=success');
exit;