<?php

session_start();

// Simpele toegang voor gebruiker
$_SESSION['can_download'] = true;

require_once __DIR__ . '/../config/db.php';

$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';

// Bestanden uit database halen
$stmt = $pdo->query("SELECT * FROM files ORDER BY uploaded_at DESC");
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Secure File Transfer</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<main class="container">
    <h1>Secure File Transfer</h1>

    <?php if (!empty($message)): ?>
        <p class="message <?= htmlspecialchars($type) ?>">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <section class="card">
        <h2>Bestand uploaden</h2>

        <form action="../server/upload.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="file" required>
            <button type="submit">Uploaden</button>
        </form>
    </section>

    <section class="card">
        <h2>Beschikbare bestanden</h2>

        <?php if (empty($files)): ?>
            <p>Er zijn nog geen bestanden.</p>
        <?php else: ?>
            <div class="file-list">
                <?php foreach ($files as $file): ?>
                    <div class="file-card">
                        <div class="file-info">
                            <h3><?= htmlspecialchars($file['original_name']) ?></h3>

                            <div class="file-meta">
                                <span><?= htmlspecialchars(strtoupper($file['file_type'])) ?></span>
                                <span><?= round($file['file_size'] / 1024, 2) ?> KB</span>
                                <span>MD5: <?= htmlspecialchars(substr($file['md5_hash'], 0, 8)) ?>...</span>
                            </div>
                        </div>

                        <a class="download-btn" href="../server/download.php?id=<?= $file['id'] ?>">
                            Download
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

</body>
</html>