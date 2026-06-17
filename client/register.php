<?php
session_start();

$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Registreren</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<main class="container">
    <section class="card login-card">
        <h1>Registreren</h1>
        <p>Maak een account aan om bestanden te uploaden en downloaden.</p>

        <?php if (!empty($message)): ?>
            <p class="message <?= htmlspecialchars($type) ?>">
                <?= htmlspecialchars($message) ?>
            </p>
        <?php endif; ?>

        <form class="login-form" action="../server/register_process.php" method="POST">
            <label for="username">Gebruikersnaam</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Wachtwoord</label>
            <input type="password" name="password" id="password" required>

            <label for="confirm_password">Herhaal wachtwoord</label>
            <input type="password" name="confirm_password" id="confirm_password" required>

            <button type="submit">Registreren</button>
        </form>

        <p class="auth-link">
            Heb je al een account?
            <a href="login.php">Inloggen</a>
        </p>
    </section>
</main>

</body>
</html>