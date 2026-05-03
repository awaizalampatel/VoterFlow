<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$pdo    = getDBConnection();

// Update preferences
if (isset($input['state_province']) || isset($input['registration_status'])) {
    $fields = [];
    $params = [];
    if (isset($input['state_province'])) {
        $fields[] = 'state_province = ?';
        $params[]  = $input['state_province'];
    }
    if (isset($input['registration_status'])) {
        $fields[] = 'registration_status = ?';
        $params[]  = $input['registration_status'];
    }
    $params[] = $userId;
    $pdo->prepare("UPDATE user_preferences SET " . implode(', ', $fields) . " WHERE user_id = ?")->execute($params);
}

// Update milestones
if (isset($input['milestone'])) {
    $allowed = ['is_registered', 'ballot_requested', 'voted'];
    $col     = $input['milestone'];
    if (in_array($col, $allowed)) {
        $pdo->prepare("UPDATE user_milestones SET $col = TRUE WHERE user_id = ?")->execute([$userId]);
    }
}

// Fetch latest data
$prefs = $pdo->prepare("SELECT state_province, registration_status FROM user_preferences WHERE user_id = ?");
$prefs->execute([$userId]);

$miles = $pdo->prepare("SELECT is_registered, ballot_requested, voted FROM user_milestones WHERE user_id = ?");
$miles->execute([$userId]);

header('Content-Type: application/json');
echo json_encode([
    'preferences' => $prefs->fetch(PDO::FETCH_ASSOC),
    'milestones'  => $miles->fetch(PDO::FETCH_ASSOC)
]);
