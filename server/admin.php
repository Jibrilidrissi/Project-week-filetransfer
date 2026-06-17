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
            // Haal bestandsnaam op voor unlink()
            $stmt = $conn->prepare('SELECT data FROM files WHERE id = ?');
            $stmt->execute([$fileId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($file) {
                $fysiekPad = $uploadDir . $file['data'];
                if (file_exists($fysiekPad)) {
                    unlink($fysiekPad);
                }
                $del = $conn->prepare('DELETE FROM files WHERE id = ?');
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

// Alle gebruikers
try {
    $users = $conn->query('SELECT id, email, created_at FROM users ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}

// Alle bestanden (met eigenaar via JOIN)
try {
    $stmt = $conn->query(
        'SELECT f.id, f.name, f.data, f.uploaded_date, u.email AS eigenaar
         FROM files f
         LEFT JOIN users u ON f.user_id = u.id
         ORDER BY f.id DESC'
    );
    $allesBestanden = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allesBestanden = [];
}

// Helper: formatteer bytes naar leesbare grootte
function formatBytes(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576)    return round($bytes / 1048576, 2)    . ' MB';
    if ($bytes >= 1024)       return round($bytes / 1024, 2)       . ' KB';
    return $bytes . ' B';
}

// Bouw de deellink-basis URL
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
$dir      = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
$basePath = $protocol . '://' . $host . ($dir === '/' ? '' : $dir);
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
            <h1 class="header__logo">FileTransfer</h1>
            <span class="badge--admin">ADMIN</span>
        </div>
        <div class="header__user header__nav">
            <span>Ingelogd als: <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong></span>
            <a href="voorpagina.php" class="btn btn--back">← Dashboard</a>
            <a href="../client/index.php?action=logout" class="btn btn--logout">Uitloggen</a>
        </div>
    </div>
</header>

<main class="admin-container">

    <?php if ($melding): ?>
        <div class="alert alert--<?php echo $meldingType; ?> alert--admin">
            <?php echo htmlspecialchars($melding); ?>
        </div>
    <?php endif; ?>

    <!-- ── Statistieken ───────────────────────────────────────────── -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card__icon">👥</div>
            <div class="stat-card__value"><?php echo (int)$totalUsers; ?></div>
            <div class="stat-card__label">Gebruikers</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__icon">📁</div>
            <div class="stat-card__value"><?php echo (int)$totalFiles; ?></div>
            <div class="stat-card__label">Bestanden</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__icon">💾</div>
            <div class="stat-card__value"><?php echo formatBytes((int)$totalSize); ?></div>
            <div class="stat-card__label">Totale opslagruimte</div>
        </div>
    </div>

    <!-- ── Gebruikersbeheer ───────────────────────────────────────── -->
    <section class="admin-section">
        <div class="admin-section__header">
            <h2 class="admin-section__title">Gebruikersbeheer</h2>
            <span class="admin-section__badge"><?php echo count($users); ?></span>
        </div>

        <?php if (empty($users)): ?>
            <p class="empty-state">Geen gebruikers gevonden.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>E-mail</th>
                            <th>Geregistreerd op</th>
                            <th>Actie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo (int)$user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['created_at'] ?? '—'); ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Weet je zeker dat je deze gebruiker EN al zijn bestanden wilt verwijderen?');">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn--danger">🗑 Verwijder</button>
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
            <h2 class="admin-section__title">Bestandsbeheer</h2>
            <span class="admin-section__badge"><?php echo count($allesBestanden); ?></span>
        </div>

        <?php if (empty($allesBestanden)): ?>
            <p class="empty-state">Nog geen bestanden geüpload.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Bestandsnaam</th>
                            <th>Eigenaar</th>
                            <th>Grootte</th>
                            <th>Geüpload op</th>
                            <th>Deellink</th>
                            <th>Actie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allesBestanden as $bestand): ?>
                            <?php
                                $fysiekPad   = $uploadDir . $bestand['data'];
                                $bestandsGrootte = file_exists($fysiekPad) ? formatBytes(filesize($fysiekPad)) : '—';
                                $shareUrl    = $basePath . '/download.php?id=' . $bestand['id'];
                            ?>
                            <tr>
                                <td><?php echo (int)$bestand['id']; ?></td>
                                <td>
                                    <div class="file-name" title="<?php echo htmlspecialchars($bestand['name']); ?>">
                                        <?php echo htmlspecialchars($bestand['name']); ?>
                                    </div>
                                </td>
                                <td class="owner-tag"><?php echo htmlspecialchars($bestand['eigenaar'] ?? '—'); ?></td>
                                <td><?php echo $bestandsGrootte; ?></td>
                                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($bestand['uploaded_date']))); ?></td>
                                <td>
                                    <div class="share-container">
                                        <input type="text" readonly value="<?php echo htmlspecialchars($shareUrl); ?>"
                                               class="form-control share-input" onclick="this.select();">
                                        <button class="btn btn--share"
                                                onclick="copyLink('<?php echo htmlspecialchars($shareUrl, ENT_QUOTES); ?>', this)">
                                            Kopieer
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Bestand permanent verwijderen?');">
                                        <input type="hidden" name="file_id" value="<?php echo (int)$bestand['id']; ?>">
                                        <button type="submit" name="delete_file" class="btn btn--danger">🗑 Verwijder</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</main>

<script>
function copyLink(url, btn) {
    navigator.clipboard.writeText(url).then(function () {
        const orig = btn.textContent;
        btn.textContent = 'Gekopieerd!';
        btn.style.background = '#10b981';
        btn.style.color = '#fff';
        setTimeout(function () {
            btn.textContent = orig;
            btn.style.background = '';
            btn.style.color = '';
        }, 2000);
    }).catch(function (err) {
        alert('Fout bij kopiëren: ' + err);
    });
}
</script>

</body>
</html>
