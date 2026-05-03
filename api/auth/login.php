<?php
session_start();
require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    header('Location: ../../index.php?error=invalid_input');
    exit;
}

try {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare("SELECT user_id, name, email, password FROM users WHERE email = ? AND oauth_provider = 'local'");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        header('Location: ../../index.php?error=invalid_credentials');
        exit;
    }

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['email']   = $user['email'];

    header('Location: ../../dashboard.php');
} catch (PDOException $e) {
    header('Location: ../../index.php?error=server_error');
}
exit;
