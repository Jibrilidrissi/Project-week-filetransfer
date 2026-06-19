<?php
session_start();

// ── Beveiliging: alleen admins mogen deze pagina zien ──────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../client/index.php');
    exit();
}

require_once __DIR__ . '/../config/db.php';

$uploadDir = __DIR__ . '/../uploads/';
$melding    = '';
$meldingType = '';

// ── Acties verwerken (verwijder bestand / verwijder gebruiker) ──────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Bestand verwijderen ---
    if (isset($_POST['delete_file']) && !empty($_POST['file_id'])) {
        $fileId = (int) $_POST['file_id'];

        try {
            // Haal bestandsnaam op voor unlink() via file_id
            $stmt = $conn->prepare('SELECT data FROM files WHERE file_id = ?');
            $stmt->execute([$fileId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($file) {
                $fysiekPad = $uploadDir . $file['data'];
                if (file_exists($fysiekPad)) {
                    unlink($fysiekPad);
                }
                $del = $conn->prepare('DELETE FROM files WHERE file_id = ?');
                $del->execute([$fileId]);
                $melding     = 'Bestand succesvol verwijderd.';
                $meldingType = 'success';
            } else {
                $melding     = 'Bestand niet gevonden.';
                $meldingType = 'error';
            }
        } catch (PDOException $e) {
            $melding     = 'Fout bij verwijderen: ' . htmlspecialchars($e->getMessage());
            $meldingType = 'error';
        }
    }

    // --- Gebruiker verwijderen (inclusief zijn bestanden) ---
    if (isset($_POST['delete_user']) && !empty($_POST['user_id'])) {
        $userId = (int) $_POST['user_id'];

        try {
            // Haal alle bestanden van deze gebruiker op om fysiek te verwijderen
            $stmt = $conn->prepare('SELECT data FROM files WHERE user_id = ?');
            $stmt->execute([$userId]);
            $userFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($userFiles as $uf) {
                $fysiekPad = $uploadDir . $uf['data'];
                if (file_exists($fysiekPad)) {
                    unlink($fysiekPad);
                }
            }

            // Verwijder bestanden uit DB
            $del = $conn->prepare('DELETE FROM files WHERE user_id = ?');
            $del->execute([$userId]);

            // Verwijder gebruiker uit DB
            $del = $conn->prepare('DELETE FROM users WHERE id = ?');
            $del->execute([$userId]);

            $melding     = 'Gebruiker en alle bijbehorende bestanden zijn verwijderd.';
            $meldingType = 'success';
        } catch (PDOException $e) {
            $melding     = 'Fout bij verwijderen: ' . htmlspecialchars($e->getMessage());
            $meldingType = 'error';
        }
    }
}

// ── Data ophalen voor het dashboard ────────────────────────────────────────

// Statistieken
try {
    $totalUsers = $conn->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $totalFiles = $conn->query('SELECT COUNT(*) FROM files')->fetchColumn();

    // Bereken de totale bestandsgrootte rechtstreeks via het filesystem
    $totalSize = 0;
    if (is_dir($uploadDir)) {
        foreach (new DirectoryIterator($uploadDir) as $fileInfo) {
            if ($fileInfo->isFile()) {
                $totalSize += $fileInfo->getSize();
            }
        }
    }
} catch (PDOException $e) {
    $totalUsers = $totalFiles = $totalSize = 0;
}

// Alle gebruikers — gebruik SELECT * zodat ontbrekende kolommen geen fatale fout geven
$usersError = '';
try {
    $users = $conn->query('SELECT * FROM users ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $usersError = $e->getMessage();
}

// Alle bestanden (met eigenaar via JOIN)
$filesError = '';
try {
    $stmt = $conn->query(
        'SELECT f.id, f.file_id, f.name, f.data, f.uploaded_date, u.email AS eigenaar
         FROM files f
         LEFT JOIN users u ON f.user_id = u.id
         ORDER BY f.id DESC'
    );
    $allesBestanden = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allesBestanden = [];
    $filesError = $e->getMessage();
}

// Downloadlog (wie, wanneer, welk bestand)
$downloadLogsError = '';
try {
    $stmt = $conn->query(
        'SELECT file_id, file_name, downloader_email, downloaded_at
         FROM download_logs
         ORDER BY downloaded_at DESC
         LIMIT 100'
    );
    $downloadLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $downloadLogs = [];
    $downloadLogsError = $e->getMessage();
}

// Helper: formatteer bytes naar leesbare grootte
function formatBytes(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576)    return round($bytes / 1048576, 2)    . ' MB';
    if ($bytes >= 1024)       return round($bytes / 1024, 2)       . ' KB';
    return $bytes . ' B';
}


?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – FileTransfer</title>
    <link rel="stylesheet" href="../client/style.css?v=<?php echo filemtime(__DIR__ . '/../client/style.css'); ?>">
</head>
<body>

<header class="header">
    <div class="header__container header__container--wide">
        <div class="header__brand">
            <a href="voorpagina.php" class="header__logo">
                <svg class="header__logo-icon" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <span>FileTransfer</span>
            </a>
            <span class="badge--admin">ADMIN PANEL</span>
        </div>
        <div class="header__user header__nav">
            <span>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong></span>
            <a href="voorpagina.php" class="btn btn--back">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Dashboard
            </a>
            <a href="../client/index.php?action=logout" class="btn btn--logout">Sign Out</a>
        </div>
    </div>
</header>

<main class="admin-container">

    <?php if ($melding): ?>
        <div class="alert alert--<?php echo $meldingType; ?> alert--admin">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span><?php echo htmlspecialchars($melding); ?></span>
        </div>
    <?php endif; ?>

    <!-- ── Stats Panel ───────────────────────────────────────────── -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card__content">
                <div>
                    <div class="stat-card__value"><?php echo (int)$totalUsers; ?></div>
                    <div class="stat-card__label">Users Registered</div>
                </div>
                <div class="stat-card__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__content">
                <div>
                    <div class="stat-card__value"><?php echo (int)$totalFiles; ?></div>
                    <div class="stat-card__label">Files Uploaded</div>
                </div>
                <div class="stat-card__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card__content">
                <div>
                    <div class="stat-card__value"><?php echo formatBytes((int)$totalSize); ?></div>
                    <div class="stat-card__label">Total Disk Usage</div>
                </div>
                <div class="stat-card__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Gebruikersbeheer ───────────────────────────────────────── -->
    <section class="admin-section">
        <div class="admin-section__header">
            <h2 class="admin-section__title">User Administration</h2>
            <span class="admin-section__badge"><?php echo count($users); ?> active</span>
        </div>

        <?php if ($usersError): ?>
            <div class="alert alert--error alert--admin">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <span>Database error: <?php echo htmlspecialchars($usersError); ?></span>
            </div>
        <?php elseif (empty($users)): ?>
            <p class="empty-state">No users registered in database.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>E-mail</th>
                            <th>Registration Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><code>#<?php echo (int)$user['id']; ?></code></td>
                                <td style="font-weight: 500; color: var(--text-primary);">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                        <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($user['email']); ?>', this)" title="Copy Email">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $rawDate = (!empty($user['Registratie_datum']) ? $user['Registratie_datum'] : null)
                                        ?? $user['created_at']
                                        ?? $user['registered_at']
                                        ?? $user['register_date']
                                        ?? $user['date']
                                        ?? null;
                                    echo htmlspecialchars($rawDate ? date('d M Y', strtotime($rawDate)) : '—');
                                    ?>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Confirm deletion of this user and all associated files? This cannot be undone.');" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn--danger">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            Delete User
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- ── Bestandsbeheer ─────────────────────────────────────────── -->
    <section class="admin-section">
        <div class="admin-section__header">
            <h2 class="admin-section__title">File System Administration</h2>
            <span class="admin-section__badge"><?php echo count($allesBestanden); ?> stored</span>
        </div>

        <?php if ($filesError): ?>
            <div class="alert alert--error alert--admin">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <span>Database error: <?php echo htmlspecialchars($filesError); ?></span>
            </div>
        <?php elseif (empty($allesBestanden)): ?>
            <p class="empty-state">No files have been transmitted yet.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>File ID</th>
                            <th>Filename</th>
                            <th>Owner / Uploader</th>
                            <th>Size</th>
                            <th>Upload Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allesBestanden as $bestand): ?>
                            <?php
                                $fysiekPad   = $uploadDir . $bestand['data'];
                                $bestandsGrootte = file_exists($fysiekPad) ? formatBytes(filesize($fysiekPad)) : '—';
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <code>#<?php echo htmlspecialchars($bestand['file_id']); ?></code>
                                        <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($bestand['file_id']); ?>', this)" title="Copy File ID">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <div class="file-name" title="<?php echo htmlspecialchars($bestand['name']); ?>" style="display: flex; align-items: center; gap: 0.5rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                        <?php echo htmlspecialchars($bestand['name']); ?>
                                    </div>
                                </td>
                                <td class="owner-tag"><?php echo htmlspecialchars($bestand['eigenaar'] ?? '—'); ?></td>
                                <td><span style="font-family: var(--font-mono); font-size: 0.85rem; color: var(--text-muted);"><?php echo $bestandsGrootte; ?></span></td>
                                <td><?php echo htmlspecialchars(date('d M Y', strtotime($bestand['uploaded_date']))); ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this file?');" style="display: inline;">
                                        <input type="hidden" name="file_id" value="<?php echo htmlspecialchars($bestand['file_id']); ?>">
                                        <button type="submit" name="delete_file" class="btn btn--danger">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- ── Downloadlog ──────────────────────────────────────────── -->
    <section class="admin-section">
        <div class="admin-section__header">
            <h2 class="admin-section__title">Downloadlog</h2>
            <span class="admin-section__badge"><?php echo count($downloadLogs); ?> recent</span>
        </div>

        <?php if ($downloadLogsError): ?>
            <div class="alert alert--error alert--admin">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <span>Database error: <?php echo htmlspecialchars($downloadLogsError); ?></span>
            </div>
        <?php elseif (empty($downloadLogs)): ?>
            <p class="empty-state">Nog geen downloads gelogd.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Wanneer</th>
                            <th>Gebruiker</th>
                            <th>Bestand</th>
                            <th>File ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($downloadLogs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('d M Y H:i', strtotime($log['downloaded_at']))); ?></td>
                                <td style="font-weight: 500; color: var(--text-primary);">
                                    <?php echo htmlspecialchars($log['downloader_email']); ?>
                                </td>
                                <td>
                                    <div class="file-name" title="<?php echo htmlspecialchars($log['file_name']); ?>" style="display: flex; align-items: center; gap: 0.5rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                        <?php echo htmlspecialchars($log['file_name']); ?>
                                    </div>
                                </td>
                                <td><code>#<?php echo htmlspecialchars($log['file_id']); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</main>

<script src="../client/script.js"></script>
</body>
</html>
