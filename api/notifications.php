<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

$pdo    = getDBConnection();
$userId = $_SESSION['user_id'];
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['mark_read'])) {
        $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$userId]);
        echo json_encode(['success'=>true]); exit;
    }
}

// GET: fetch notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$userId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
