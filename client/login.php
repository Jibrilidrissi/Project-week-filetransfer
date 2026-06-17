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
    <section class="card login-card">
        <h1>Inloggen</h1>
        <p>Log in om bestanden te uploaden of downloaden.</p>

        <?php if (!empty($message)): ?>
            <p class="message <?= htmlspecialchars($type) ?>">
                <?= htmlspecialchars($message) ?>
            </p>
        <?php endif; ?>

        <form class="login-form" action="../server/login_process.php" method="POST">
            <label for="username">Gebruikersnaam</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Wachtwoord</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">Inloggen</button>
        </form>
    </section>
</main>

</body>
</html>