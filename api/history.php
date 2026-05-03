<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) { http_response_code(401); exit; }

$pdo = getDBConnection();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $pdo->prepare("DELETE FROM chat_history WHERE user_id = ?")->execute([$_SESSION['user_id']]);
    echo json_encode(['success' => true]);
    exit;
}

// Save dark_mode preference
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['dark_mode'])) {
        $pdo->prepare("UPDATE user_preferences SET dark_mode = ? WHERE user_id = ?")->execute([(int)$input['dark_mode'], $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
        exit;
    }
}

// GET: fetch last 100 messages
$stmt = $pdo->prepare("SELECT role, message, created_at FROM chat_history WHERE user_id = ? ORDER BY created_at ASC LIMIT 100");
$stmt->execute([$_SESSION['user_id']]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
