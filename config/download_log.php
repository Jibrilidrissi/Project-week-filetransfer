<?php

function logDownload(PDO $conn, array $file, ?int $userId, string $downloaderEmail): void
{
    try {
        $stmt = $conn->prepare(
            'INSERT INTO download_logs (file_id, file_name, user_id, downloader_email, downloaded_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $file['file_id'],
            $file['name'],
            $userId,
            $downloaderEmail,
        ]);
    } catch (PDOException $e) {
        // Logging mag de download niet blokkeren
    }
}
