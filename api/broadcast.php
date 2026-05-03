<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['user_id'])) { http_response_code(401); exit; }

$pdo = getDBConnection();
$isAdmin = $pdo->prepare("SELECT is_admin FROM users WHERE user_id=?");
$isAdmin->execute([$_SESSION['user_id']]);
if (!$isAdmin->fetchColumn()) { http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit; }

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $msg   = trim($input['message'] ?? '');
    if (!$msg) { echo json_encode(['error'=>'Empty message']); exit; }

    $users = $pdo->query("SELECT user_id FROM users")->fetchAll(PDO::FETCH_COLUMN);
    $stmt  = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)");
    foreach ($users as $uid) $stmt->execute([$uid, $msg]);
    echo json_encode(['success'=>true, 'sent'=>count($users)]); exit;
}

// GET: analytics
$analytics = [
    'users_per_day'    => $pdo->query("SELECT DATE(created_at) as d, COUNT(*) as c FROM users GROUP BY DATE(created_at) ORDER BY d DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC),
    'chats_per_day'    => $pdo->query("SELECT DATE(created_at) as d, COUNT(*) as c FROM chat_history GROUP BY DATE(created_at) ORDER BY d DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC),
    'top_countries'    => $pdo->query("SELECT state_province as country, COUNT(*) as c FROM user_preferences WHERE state_province IS NOT NULL AND state_province != '' GROUP BY state_province ORDER BY c DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC),
    'reactions'        => $pdo->query("SELECT reaction, COUNT(*) as c FROM chat_reactions GROUP BY reaction")->fetchAll(PDO::FETCH_ASSOC),
];
echo json_encode($analytics);
