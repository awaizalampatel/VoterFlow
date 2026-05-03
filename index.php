<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
require_once 'config.php';
$google_auth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'access_type'   => 'online'
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoterFlow – Your Election Assistant</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-wrapper">

        <!-- Left Panel -->
        <div class="auth-left">
            <div class="auth-left-inner">
                <div class="auth-brand-row">
                    <div class="brand-logo">🗳️</div>
                    <h1>VoterFlow</h1>
                </div>
                <p class="brand-tagline">Your personal guide to the democratic process</p>
                <ul class="feature-list">
                    <li><span class="feat-icon"><span class="material-icons-round">smart_toy</span></span> AI-powered election guidance</li>
                    <li><span class="feat-icon"><span class="material-icons-round">location_on</span></span> Personalized to your region</li>
                    <li><span class="feat-icon"><span class="material-icons-round">verified_user</span></span> Non-partisan &amp; secure</li>
                    <li><span class="feat-icon"><span class="material-icons-round">task_alt</span></span> Track your voting milestones</li>
                </ul>
            </div>
            <div class="auth-left-footer">© 2025 VoterFlow. All rights reserved.</div>
        </div>

        <!-- Right Panel -->
        <div class="auth-right">
            <div class="auth-card">
                <div class="auth-card-header">
                    <h2 id="cardTitle">Welcome back</h2>
                    <p id="cardSubtitle">Sign in to your VoterFlow account</p>
                </div>

                <div class="tab-switcher">
                    <button class="tab-btn active" onclick="switchTab('login')">Sign In</button>
                    <button class="tab-btn" onclick="switchTab('signup')">Sign Up</button>
                </div>

                <!-- Login Form -->
                <form id="loginForm" class="auth-form" action="api/auth/login.php" method="POST">
                    <div id="loginError" class="error-msg" style="display:none"></div>
                    <div class="form-group">
                        <label>Email address</label>
                        <div class="input-wrap">
                            <span class="input-icon"><span class="material-icons-round">mail</span></span>
                            <input type="email" name="email" placeholder="you@example.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrap">
                            <span class="input-icon"><span class="material-icons-round">lock</span></span>
                            <input type="password" id="loginPassword" name="password" placeholder="••••••••" required>
                            <button type="button" class="toggle-pw" onclick="togglePw('loginPassword', this)"><span class="material-icons-round">visibility</span></button>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Sign In →</button>
                </form>

                <!-- Signup Form -->
                <form id="signupForm" class="auth-form" style="display:none" action="api/auth/signup.php" method="POST">
                    <div id="signupError" class="error-msg" style="display:none"></div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <div class="input-wrap">
                            <span class="input-icon"><span class="material-icons-round">person</span></span>
                            <input type="text" name="name" placeholder="John Doe" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email address</label>
                        <div class="input-wrap">
                            <span class="input-icon"><span class="material-icons-round">mail</span></span>
                            <input type="email" name="email" placeholder="you@example.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrap">
                            <span class="input-icon"><span class="material-icons-round">lock</span></span>
                            <input type="password" id="signupPassword" name="password" placeholder="Min. 8 characters" minlength="8" required>
                            <button type="button" class="toggle-pw" onclick="togglePw('signupPassword', this)"><span class="material-icons-round">visibility</span></button>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Create Account →</button>
                </form>

                <div class="divider"><span>or continue with</span></div>

                <a href="<?= htmlspecialchars($google_auth_url) ?>" class="btn-google">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google">
                    Continue with Google
                </a>

                <p class="privacy-note"><span class="material-icons-round" style="font-size:13px;vertical-align:middle">lock</span> Encrypted &amp; secure. We never share your data.</p>
            </div>
        </div>

    </div>
    <script src="assets/app.js"></script>
</body>
</html>
