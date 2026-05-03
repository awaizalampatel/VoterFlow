<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }

$pdo  = getDBConnection();
$stmt = $pdo->prepare("SELECT role, message, created_at FROM chat_history WHERE user_id=? ORDER BY created_at ASC");
$stmt->execute([$_SESSION['user_id']]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="voterflow-chat.txt"');

echo "VoterFlow Chat Export\n";
echo "User: " . htmlspecialchars($_SESSION['name']) . "\n";
echo "Exported: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 50) . "\n\n";

foreach ($rows as $r) {
    $label = strtoupper($r['role']);
    echo "[{$r['created_at']}] {$label}:\n{$r['message']}\n\n";
}
