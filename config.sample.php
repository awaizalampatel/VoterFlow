<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'voterflow');
define('DB_USER', 'root');
define('DB_PASS', '');

define('OPENAI_API_KEY', 'YOUR_OPENAI_API_KEY_HERE');

define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
define('GOOGLE_REDIRECT_URI', 'http://localhost/VoterFlow/api/auth/google_callback.php');

define('NEWSAPI_KEY', 'YOUR_NEWSAPI_KEY_HERE'); // Get free key at https://newsapi.org

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    return $pdo;
}
