<?php
session_start();
require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php');
    exit;
}

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$name || !$email || !$password || strlen($password) < 8) {
    header('Location: ../../index.php?error=invalid_input#signup');
    exit;
}

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header('Location: ../../index.php?error=email_exists#signup');
        exit;
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, oauth_provider) VALUES (?, ?, ?, 'local')");
    $stmt->execute([$name, $email, $hashed]);
    $userId = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO user_preferences (user_id) VALUES (?)")->execute([$userId]);
    $pdo->prepare("INSERT INTO user_milestones (user_id) VALUES (?)")->execute([$userId]);

    $_SESSION['user_id'] = $userId;
    $_SESSION['name']    = $name;
    $_SESSION['email']   = $email;

    header('Location: ../../dashboard.php');
} catch (PDOException $e) {
    header('Location: ../../index.php?error=server_error#signup');
}
exit;
