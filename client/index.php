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
            // Hash het wachtwoord voordat het wordt opgeslagen
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (email, password, Registratie_datum) VALUES (?, ?, ?)");
            if ($stmt->execute([$email, $hashedPassword, $registratieDatum])) {
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
    <title>Secure Login - FileTransfer</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="login-page">
    <div class="login-page__card">
        <div class="login-page__logo-row">
            <svg class="login-page__logo-icon" xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            <h1 class="login-page__title">FileTransfer</h1>
        </div>

        <p class="login-page__subtitle">Access your secure encrypted storage vault</p>

        <?php if($error): ?>
            <p class="login-page__error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="login-page__input-group">
                <label class="login-page__label">Email Address</label>
                <div class="login-page__input-wrapper">
                    <input class="login-page__input" type="email" name="email" required placeholder="name@company.com">
                    <svg class="login-page__input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
            </div>

            <div class="login-page__input-group">
                <label class="login-page__label">Password</label>
                <div class="login-page__input-wrapper">
                    <input class="login-page__input" type="password" name="password" required placeholder="••••••••">
                    <svg class="login-page__input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
            </div>

            <div class="login-page__button-container">
                <button class="login-page__button" type="submit" name="action" value="login">Sign In</button>
                <button class="login-page__button login-page__button--secondary" type="submit" name="action" value="register">Create Free Account</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>