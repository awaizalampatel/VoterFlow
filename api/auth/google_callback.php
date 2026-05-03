<?php
session_start();
require_once '../../config.php';

$code = $_GET['code'] ?? '';
if (!$code) {
    header('Location: ../../index.php?error=oauth_failed');
    exit;
}

// Exchange code for access token
$tokenResponse = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query([
            'code'          => $code,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code'
        ])
    ]
]));

$tokenData   = json_decode($tokenResponse, true);
$accessToken = $tokenData['access_token'] ?? '';

if (!$accessToken) {
    header('Location: ../../index.php?error=oauth_failed');
    exit;
}

// Get user info from Google
$userInfoResponse = file_get_contents('https://www.googleapis.com/oauth2/v2/userinfo', false, stream_context_create([
    'http' => ['header' => "Authorization: Bearer $accessToken"]
]));

$googleUser = json_decode($userInfoResponse, true);
$googleId   = $googleUser['id'] ?? '';
$email      = $googleUser['email'] ?? '';
$name       = $googleUser['name'] ?? '';

if (!$googleId || !$email) {
    header('Location: ../../index.php?error=oauth_failed');
    exit;
}

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE oauth_id = ? OR email = ?");
    $stmt->execute([$googleId, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $userId = $user['user_id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, oauth_provider, oauth_id) VALUES (?, ?, 'google', ?)");
        $stmt->execute([$name, $email, $googleId]);
        $userId = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO user_preferences (user_id) VALUES (?)")->execute([$userId]);
        $pdo->prepare("INSERT INTO user_milestones (user_id) VALUES (?)")->execute([$userId]);
    }

    $_SESSION['user_id'] = $userId;
    $_SESSION['name']    = $name;
    $_SESSION['email']   = $email;

    header('Location: ../../dashboard.php');
} catch (PDOException $e) {
    header('Location: ../../index.php?error=server_error');
}
exit;
