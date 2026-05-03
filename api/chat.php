<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$history = $input['history'] ?? [];

if (!$message) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty message']);
    exit;
}

// Fetch user preferences for personalization
$pdo  = getDBConnection();
$stmt = $pdo->prepare("SELECT state_province, registration_status FROM user_preferences WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$prefs = $stmt->fetch(PDO::FETCH_ASSOC);

$country = $prefs['state_province'] ?? '';
$regStatus = $prefs['registration_status'] ?? 'unknown';

if (!$country) $country = 'unknown (user has not selected a country yet)';

// Fetch upcoming election events for user's region
$events = [];
if ($prefs['state_province']) {
    $evStmt = $pdo->prepare("SELECT event_name, event_date FROM election_events WHERE region = ? ORDER BY event_date ASC");
    $evStmt->execute([$prefs['state_province']]);
    $events = $evStmt->fetchAll(PDO::FETCH_ASSOC);
}

$eventsText = '';
foreach ($events as $e) {
    $eventsText .= "- {$e['event_name']}: {$e['event_date']}\n";
}

$systemPrompt = "You are VoterFlow AI, a strictly non-partisan civic education assistant specializing in democratic processes and election procedures worldwide.

The user is from: $country.

Your job is to give SPECIFIC, ACCURATE guidance for $country including:
- How voter registration works in $country (eligibility, process, documents needed)
- The type of electoral system used in $country (e.g. parliamentary, presidential, proportional representation)
- How elections are conducted in $country (polling stations, voting methods, ID requirements)
- Upcoming or recent elections in $country
- How to request mail-in or absentee ballots if available in $country
- Key civic rights and responsibilities for voters in $country
- Official government election websites and resources for $country
" . ($eventsText ? "Known upcoming election events:\n$eventsText\n" : "") . "
Rules:
- Always tailor your answer specifically to $country — never give generic answers.
- Never endorse any candidate, party, or political opinion.
- Use simple language, avoid legal jargon.
- Break steps into numbered lists where helpful.
- If you don't know something specific about $country, say so and direct them to the official election authority of $country.
- If no country is set, ask the user to select their country from the sidebar first.";

$messages = [['role' => 'system', 'content' => $systemPrompt]];
foreach ($history as $h) {
    if (in_array($h['role'] ?? '', ['user', 'assistant'])) {
        $messages[] = ['role' => $h['role'], 'content' => $h['content']];
    }
}
$messages[] = ['role' => 'user', 'content' => $message];

$payload = json_encode([
    'model'    => 'gpt-3.5-turbo',
    'messages' => $messages,
    'max_tokens' => 600
]);

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT        => 30,
]);

$response  = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError) {
    header('Content-Type: application/json');
    echo json_encode(['reply' => 'Connection error: ' . $curlError]);
    exit;
}

$data = json_decode($response, true);

if ($httpCode !== 200) {
    $errMsg = $data['error']['message'] ?? 'Unknown API error (HTTP ' . $httpCode . ')';
    header('Content-Type: application/json');
    echo json_encode(['reply' => 'OpenAI error: ' . $errMsg]);
    exit;
}

$reply = $data['choices'][0]['message']['content'] ?? 'Sorry, I could not process that. Please try again.';

// Save to chat history
$pdo->prepare("INSERT INTO chat_history (user_id, role, message) VALUES (?, 'user', ?)")->execute([$_SESSION['user_id'], $message]);
$pdo->prepare("INSERT INTO chat_history (user_id, role, message) VALUES (?, 'assistant', ?)")->execute([$_SESSION['user_id'], $reply]);

header('Content-Type: application/json');
echo json_encode(['reply' => $reply]);
