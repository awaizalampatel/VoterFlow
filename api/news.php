<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

header('Content-Type: application/json');

$country = trim($_GET['country'] ?? '');
if (!$country) { echo json_encode(['articles'=>[]]); exit; }

$query   = urlencode("election $country");
$apiKey  = defined('NEWSAPI_KEY') ? NEWSAPI_KEY : '';
if (!$apiKey) { echo json_encode(['articles'=>[], 'note'=>'No NewsAPI key configured']); exit; }

$url = "https://newsapi.org/v2/everything?q={$query}&sortBy=publishedAt&pageSize=5&language=en&apiKey={$apiKey}";

$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>8, CURLOPT_SSL_VERIFYPEER=>false]);
$res  = curl_exec($ch);
curl_close($ch);

$data = json_decode($res, true);
$articles = array_map(fn($a) => [
    'title'  => $a['title'],
    'url'    => $a['url'],
    'source' => $a['source']['name'],
    'date'   => substr($a['publishedAt'], 0, 10),
    'image'  => $a['urlToImage'] ?? null,
], array_slice($data['articles'] ?? [], 0, 5));

echo json_encode(['articles' => $articles]);
