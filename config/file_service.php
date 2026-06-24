<?php

require_once __DIR__ . '/file_encryption.php';

function verifyFilePassword(array $file, string $password): bool
{
    return $file['password'] === $password || password_verify($password, $file['password']);
}

function storeEncryptedUpload(string $tmpPath, string $password, string $uploadDir): array
{
    $plaintext = file_get_contents($tmpPath);
    if ($plaintext === false) {
        throw new RuntimeException('Geüpload bestand kon niet worden gelezen.');
    }

    $fileHash = hash('sha256', $plaintext);
    $encrypted = encryptFileContents($plaintext, $password);
    $storedName = bin2hex(random_bytes(16)) . '_' . time() . '.enc';
    $targetPath = $uploadDir . $storedName;

    if (file_put_contents($targetPath, $encrypted) === false) {
        throw new RuntimeException('Versleuteld bestand kon niet worden opgeslagen.');
    }

    return [
        'stored_name' => $storedName,
        'file_hash' => $fileHash,
    ];
}

function streamSecureDownload(array $file, string $password, string $uploadDir, ?callable $onSuccess = null): ?string
{
    if (!verifyFilePassword($file, $password)) {
        return 'Onjuist wachtwoord voor dit bestand.';
    }

    $filepath = $uploadDir . $file['data'];
    if (!file_exists($filepath)) {
        return 'Bestand bestaat niet fysiek op de server.';
    }

    if (isEncryptedStorageName($file['data'])) {
        $encrypted = file_get_contents($filepath);
        if ($encrypted === false) {
            return 'Fout bij het lezen van het versleutelde bestand.';
        }

        // Try decrypting with the hashed password from the DB first (allows using the hashed password directly)
        $plaintext = decryptFileContents($encrypted, $file['password']);
        if ($plaintext === null) {
            // Fallback to the entered plaintext password
            $plaintext = decryptFileContents($encrypted, $password);
        }
        
        if ($plaintext === null) {
            return 'Decryptie mislukt. Het bestand is beschadigd of het wachtwoord is onjuist.';
        }

        if ($file['file_hash'] !== null && hash('sha256', $plaintext) !== $file['file_hash']) {
            return 'Bestand is aangepast. Download gestopt.';
        }

        if ($onSuccess !== null) {
            $onSuccess($file);
        }

        sendDownloadHeaders($file['name'], strlen($plaintext));
        echo $plaintext;
        exit();
    }

    $currentHash = hash_file('sha256', $filepath);
    if ($file['file_hash'] !== null && $currentHash !== $file['file_hash']) {
        return 'Bestand is aangepast. Download gestopt.';
    }

    if ($onSuccess !== null) {
        $onSuccess($file);
    }

    sendDownloadHeaders($file['name'], filesize($filepath));
    readfile($filepath);
    exit();
}

function sendDownloadHeaders(string $originalName, int $contentLength): void
{
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($originalName) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, no-store');
    header('Pragma: public');
    header('Content-Length: ' . $contentLength);

    if (ob_get_level() > 0) {
        ob_clean();
    }
    flush();
}
