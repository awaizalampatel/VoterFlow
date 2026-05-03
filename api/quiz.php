<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

$pdo    = getDBConnection();
$userId = $_SESSION['user_id'];
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $score = (int)($input['score'] ?? 0);
    $total = (int)($input['total'] ?? 0);
    $pdo->prepare("INSERT INTO quiz_results (user_id, score, total) VALUES (?,?,?)")->execute([$userId, $score, $total]);
    // Award badge if perfect score
    if ($score === $total && $total > 0) {
        $exists = $pdo->prepare("SELECT 1 FROM user_badges WHERE user_id=? AND badge_key='quiz_perfect'");
        $exists->execute([$userId]);
        if (!$exists->fetchColumn()) {
            $pdo->prepare("INSERT INTO user_badges (user_id, badge_key, badge_name, badge_icon) VALUES (?,?,?,?)")
                ->execute([$userId, 'quiz_perfect', 'Civic Scholar', '🎓']);
        }
    }
    echo json_encode(['success'=>true]); exit;
}

// GET: best score
$stmt = $pdo->prepare("SELECT score, total, created_at FROM quiz_results WHERE user_id=? ORDER BY score DESC LIMIT 1");
$stmt->execute([$userId]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: ['score'=>null]);
