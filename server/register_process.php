<?php

require_once __DIR__ . '/../config/db.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Controleer of alle velden zijn ingevuld
if (empty($username) || empty($password) || empty($confirmPassword)) {
    header('Location: ../client/register.php?message=Vul alle velden in&type=error');
    exit;
}

// Controleer wachtwoord lengte
if (strlen($password) < 6) {
    header('Location: ../client/register.php?message=Wachtwoord moet minimaal 6 tekens zijn&type=error');
    exit;
}

// Controleer of wachtwoorden hetzelfde zijn
if ($password !== $confirmPassword) {
    header('Location: ../client/register.php?message=Wachtwoorden zijn niet hetzelfde&type=error');
    exit;
}

// Controleer of gebruikersnaam al bestaat
$sql = "SELECT id FROM users WHERE username = :username";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':username' => $username
]);

$existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingUser) {
    header('Location: ../client/register.php?message=Gebruikersnaam bestaat al&type=error');
    exit;
}

// Wachtwoord veilig hashen
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Nieuwe gebruiker opslaan
$sql = "INSERT INTO users (username, password, role)
        VALUES (:username, :password, 'user')";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':username' => $username,
    ':password' => $hashedPassword
]);

header('Location: ../client/login.php?message=Account aangemaakt. Je kan nu inloggen&type=success');
exit;