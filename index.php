<?php
session_start();

// Database verbinding parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "filetransfer";

try {
    // Verbinding maken met de database via PDO
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Als de verbinding mislukt, stopt het script met een foutmelding
    die("Verbinding mislukt: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>

<div class="login-page">
    <div class="login-page__card">

        <h1 class="login-page__title">Login</h1>

        <?php if($error): ?>
            <p class="login-page__error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post">

            <div class="login-page__input-group">
                <input class="login-page__input" type="email" name="email" required placeholder=" ">
                <label class="login-page__label">Email</label>
            </div>

            <div class="login-page__input-group">
                <input class="login-page__input" type="password" name="password" required placeholder=" ">
                <label class="login-page__label">Password</label>
            </div>

            <button class="login-page__button" type="submit" name="action" value="login">Login</button>
            <button class="login-page__button login-page__button--secondary" type="submit" name="action" value="register">Register</button>


        </form>

    </div>
</div>

</body>
</html>