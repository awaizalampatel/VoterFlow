<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

$pdo    = getDBConnection();
$userId = $_SESSION['user_id'];
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input    = json_decode(file_get_contents('php://input'), true);
    $reaction = $input['reaction'] ?? ''; // 'up' or 'down'
    $msgIndex = (int)($input['msg_index'] ?? 0);
    if (!in_array($reaction, ['up','down'])) { echo json_encode(['error'=>'Invalid']); exit; }
    $pdo->prepare("INSERT INTO chat_reactions (user_id, msg_index, reaction) VALUES (?,?,?) ON DUPLICATE KEY UPDATE reaction=?")
        ->execute([$userId, $msgIndex, $reaction, $reaction]);
    echo json_encode(['success'=>true]); exit;
}
echo json_encode(['error'=>'Method not allowed']);
