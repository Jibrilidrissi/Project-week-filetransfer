<?php
session_start();

// Database verbinding via config
require_once __DIR__ . '/../config/db.php';

$error = "";

// LOGOUT LOGICA
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Controleer of het formulier is verzonden via een POST-request
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $password = $_POST["password"];
    $action = $_POST["action"] ?? "login"; // Bepaal of de actie 'login' of 'register' is

    if ($action === "register") {
        // REGISTRATIE LOGICA
        // Controleer eerst of het e-mailadres al bestaat in de tabel 'users' of 'admin'
        $stmt = $conn->prepare("SELECT email FROM users WHERE email = ? UNION SELECT email FROM admin WHERE email = ?");
        $stmt->execute([$email, $email]);
        if ($stmt->fetch()) {
            $error = "E-mailadres is al geregistreerd";
        } else {
            // Sla het wachtwoord in plaintext op
            $registratieDatum = date('Y-m-d');
            $stmt = $conn->prepare("INSERT INTO users (email, password, Registratie_datum) VALUES (?, ?, ?)");
            if ($stmt->execute([$email, $password, $registratieDatum])) {
                // Sessie initialiseren na succesvolle registratie
                session_regenerate_id(true); // Voorkomt session fixation aanvallen
                $_SESSION["user_id"] = $conn->lastInsertId();
                $_SESSION["email"] = $email;
                $_SESSION["role"] = "user";

                header("Location: ../server/voorpagina.php");
                exit();
            } else {
                $error = "Registratie mislukt. Probeer het opnieuw.";
            }
        }
    } else {
        // INLOG LOGICA
        // Controleer eerst of de gebruiker een admin is
        $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            // Controleer of het wachtwoord overeenkomt (ondersteunt zowel gehashte als platte tekst voor legacy support)
            if (password_verify($password, $admin["password"]) || $password === $admin["password"]) {
                session_regenerate_id(true);
                $_SESSION["user_id"] = $admin["id"];
                $_SESSION["email"] = $admin["email"];
                $_SESSION["role"] = "admin";

                header("Location: ../server/admin.php");
                exit();
            } else {
                $error = "Onjuist wachtwoord";
            }
        } else {
            // Als geen admin gevonden is, check de 'users' tabel
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Controleer wachtwoord voor gewone gebruiker
                if (password_verify($password, $user["password"]) || $password === $user["password"]) {
                    session_regenerate_id(true);
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["email"] = $user["email"];
                    $_SESSION["role"] = "user";

                    header("Location: ../server/voorpagina.php");
                    exit();
                } else {
                    $error = "Onjuist wachtwoord";
                }
            } else {
                $error = "Gebruiker niet gevonden";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
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