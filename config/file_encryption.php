<?php

function deriveFileEncryptionKey(string $password, string $salt): string
{
    return hash_hkdf('sha256', $password, 32, 'filetransfer-aes-256-gcm', $salt);
}

function encryptFileContents(string $plaintext, string $password): string
{
    $salt = random_bytes(16);
    $iv = random_bytes(12);
    $key = deriveFileEncryptionKey($password, $salt);
    $tag = '';

    $ciphertext = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($ciphertext === false) {
        throw new RuntimeException('Bestand kon niet worden versleuteld.');
    }

    return $salt . $iv . $tag . $ciphertext;
}

function decryptFileContents(string $encrypted, string $password): ?string
{
    if (strlen($encrypted) < 44) {
        return null;
    }

    $salt = substr($encrypted, 0, 16);
    $iv = substr($encrypted, 16, 12);
    $tag = substr($encrypted, 28, 16);
    $ciphertext = substr($encrypted, 44);
    $key = deriveFileEncryptionKey($password, $salt);

    $plaintext = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    return $plaintext === false ? null : $plaintext;
}

function isEncryptedStorageName(string $storedName): bool
{
    return str_ends_with(strtolower($storedName), '.enc');
}
