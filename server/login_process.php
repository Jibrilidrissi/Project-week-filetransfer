<?php

require_once __DIR__ . '/../config/db.php';

session_start();

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    header('Location: ../client/login.php?message=Vul alle velden in&type=error');
    exit;
}

$sql = "SELECT * FROM users WHERE username = :username";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':username' => $username
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: ../client/login.php?message=Verkeerde gegevens&type=error');
    exit;
}

if (!password_verify($password, $user['password'])) {
    header('Location: ../client/login.php?message=Verkeerde gegevens&type=error');
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

header('Location: ../client/index.php?message=Je bent ingelogd&type=success');
exit;