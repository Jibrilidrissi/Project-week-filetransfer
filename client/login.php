<?php
session_start();

$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Inloggen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<main class="container">
    <section class="card">
        <h1>Inloggen</h1>

        <?php if (!empty($message)): ?>
            <p class="message <?= htmlspecialchars($type) ?>">
                <?= htmlspecialchars($message) ?>
            </p>
        <?php endif; ?>

        <form action="../server/login_process.php" method="POST">
            <label>Gebruikersnaam</label>
            <input type="text" name="username" required>

            <label>Wachtwoord</label>
            <input type="password" name="password" required>

            <button type="submit">Inloggen</button>
        </form>
    </section>
</main>

</body>
</html>