<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
require_once 'config.php';

$pdo = getDBConnection();

$prefs = $pdo->prepare("SELECT state_province, registration_status FROM user_preferences WHERE user_id=?");
$prefs->execute([$_SESSION['user_id']]);
$userPrefs = $prefs->fetch(PDO::FETCH_ASSOC);

$miles = $pdo->prepare("SELECT is_registered, ballot_requested, voted FROM user_milestones WHERE user_id=?");
$miles->execute([$_SESSION['user_id']]);
$milestones = $miles->fetch(PDO::FETCH_ASSOC);

$completed = array_sum(array_map('intval', $milestones));
$progress  = round(($completed / 3) * 100);

$darkMode = 0;
try {
    $dm = $pdo->prepare("SELECT dark_mode FROM user_preferences WHERE user_id=?");
    $dm->execute([$_SESSION['user_id']]);
    $darkMode = (int)($dm->fetch(PDO::FETCH_ASSOC)['dark_mode'] ?? 0);
} catch (Exception $e) {}

$chatHistory = [];
try {
    $h = $pdo->prepare("SELECT role, message FROM chat_history WHERE user_id=? ORDER BY created_at ASC LIMIT 100");
    $h->execute([$_SESSION['user_id']]);
    $chatHistory = $h->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$isAdmin = false;
try {
    $a = $pdo->prepare("SELECT is_admin FROM users WHERE user_id=?");
    $a->execute([$_SESSION['user_id']]);
    $isAdmin = (bool)$a->fetchColumn();
} catch (Exception $e) {}

// Badges
$badges = [];
try {
    $b = $pdo->prepare("SELECT badge_icon, badge_name FROM user_badges WHERE user_id=?");
    $b->execute([$_SESSION['user_id']]);
    $badges = $b->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Unread notifications count
$unreadCount = 0;
try {
    $n = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $n->execute([$_SESSION['user_id']]);
    $unreadCount = (int)$n->fetchColumn();
} catch (Exception $e) {}

// Best quiz score
$quizBest = null;
try {
    $q = $pdo->prepare("SELECT score, total FROM quiz_results WHERE user_id=? ORDER BY score DESC LIMIT 1");
    $q->execute([$_SESSION['user_id']]);
    $quizBest = $q->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Next election event for user's country
$nextEvent = null;
try {
    if ($userPrefs['state_province']) {
        $ev = $pdo->prepare("SELECT event_name, event_date FROM election_events WHERE region=? AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 1");
        $ev->execute([$userPrefs['state_province']]);
        $nextEvent = $ev->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}

// Award milestone badges
try {
    if ($completed === 3) {
        $exists = $pdo->prepare("SELECT 1 FROM user_badges WHERE user_id=? AND badge_key='all_milestones'");
        $exists->execute([$_SESSION['user_id']]);
        if (!$exists->fetchColumn()) {
            $pdo->prepare("INSERT IGNORE INTO user_badges (user_id,badge_key,badge_name,badge_icon) VALUES (?,?,?,?)")
                ->execute([$_SESSION['user_id'],'all_milestones','Civic Champion','🏆']);
        }
    }
    if ($milestones['voted']) {
        $exists = $pdo->prepare("SELECT 1 FROM user_badges WHERE user_id=? AND badge_key='voted'");
        $exists->execute([$_SESSION['user_id']]);
        if (!$exists->fetchColumn()) {
            $pdo->prepare("INSERT IGNORE INTO user_badges (user_id,badge_key,badge_name,badge_icon) VALUES (?,?,?,?)")
                ->execute([$_SESSION['user_id'],'voted','Voter','🗳️']);
        }
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoterFlow – Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="VoterFlow">
    <link rel="stylesheet" href="assets/style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
</head>
<body class="dashboard-page<?= $darkMode ? ' dark' : '' ?>">

<!-- Mobile top bar -->
<div class="mobile-topbar">
    <button class="mobile-menu-btn" onclick="toggleSidebar()" aria-label="Menu">
        <span class="material-icons-round">menu</span>
    </button>
    <span class="mobile-brand">🗳️ VoterFlow</span>
    <div style="display:flex;gap:.4rem;align-items:center">
        <button class="header-icon-btn" onclick="toggleDarkMode()" id="darkBtnMobile">
            <span class="material-icons-round"><?= $darkMode ? 'light_mode' : 'dark_mode' ?></span>
        </button>
        <button class="header-icon-btn notif-btn" onclick="openPanel('notifPanel')" title="Notifications">
            <span class="material-icons-round">notifications</span>
            <?php if($unreadCount): ?><span class="notif-badge"><?= $unreadCount ?></span><?php endif; ?>
        </button>
    </div>
</div>

<!-- Sidebar overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <span class="brand-emoji">🗳️</span>
        <span class="brand-name">VoterFlow</span>
    </div>

    <div class="user-card">
        <div class="avatar"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
        <div class="user-details">
            <div class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
            <div class="user-email"><?= htmlspecialchars($_SESSION['email']) ?></div>
        </div>
    </div>

    <!-- Badges -->
    <?php if (!empty($badges)): ?>
    <div class="badges-section">
        <?php foreach ($badges as $badge): ?>
        <span class="badge-pill" title="<?= htmlspecialchars($badge['badge_name']) ?>"><?= $badge['badge_icon'] ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Progress -->
    <div class="progress-section">
        <div class="progress-label">
            <span><span class="material-icons-round" style="font-size:14px">route</span> Path to the Polls</span>
            <span class="progress-pct"><?= $progress ?>%</span>
        </div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?= $progress ?>%"></div></div>
    </div>

    <!-- Milestones -->
    <div class="milestones-section">
        <div class="milestones-title">Your Milestones</div>
        <div class="milestones">
            <div class="milestone-item <?= $milestones['is_registered'] ? 'done' : '' ?>" data-milestone="is_registered">
                <span class="milestone-icon material-icons-round"><?= $milestones['is_registered'] ? 'task_alt' : 'radio_button_unchecked' ?></span>
                <div class="milestone-text">
                    <span class="milestone-label">Voter Registration</span>
                    <span class="milestone-hint"><?= $milestones['is_registered'] ? 'Completed' : 'Tap to mark done' ?></span>
                </div>
            </div>
            <div class="milestone-item <?= $milestones['ballot_requested'] ? 'done' : '' ?>" data-milestone="ballot_requested">
                <span class="milestone-icon material-icons-round"><?= $milestones['ballot_requested'] ? 'task_alt' : 'radio_button_unchecked' ?></span>
                <div class="milestone-text">
                    <span class="milestone-label">Ballot Requested</span>
                    <span class="milestone-hint"><?= $milestones['ballot_requested'] ? 'Completed' : 'Tap to mark done' ?></span>
                </div>
            </div>
            <div class="milestone-item <?= $milestones['voted'] ? 'done' : '' ?>" data-milestone="voted">
                <span class="milestone-icon material-icons-round"><?= $milestones['voted'] ? 'task_alt' : 'radio_button_unchecked' ?></span>
                <div class="milestone-text">
                    <span class="milestone-label">Voted!</span>
                    <span class="milestone-hint"><?= $milestones['voted'] ? 'Completed' : 'Tap to mark done' ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Country Selection -->
    <div class="region-section">
        <label><span class="material-icons-round">public</span> Your Country</label>
        <div class="country-select-wrap">
            <div class="country-trigger" id="countryTrigger" onclick="toggleCountryDropdown()">
                <span id="selectedFlag" class="country-flag">🌍</span>
                <span id="selectedName" class="country-label"><?= htmlspecialchars($userPrefs['state_province'] ?? 'Select your country') ?></span>
                <span class="material-icons-round country-chevron">expand_more</span>
            </div>
            <div class="country-dropdown" id="countryDropdown">
                <div class="country-search-wrap">
                    <span class="material-icons-round">search</span>
                    <input type="text" id="countrySearch" placeholder="Search country..." oninput="filterCountries()" onclick="event.stopPropagation()">
                </div>
                <div id="countryList" class="premium-country-list sidebar-country-list">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar nav -->
    <nav class="sidebar-nav">
        <button class="snav-btn active" onclick="switchView('chat',this)"><span class="material-icons-round">chat</span> AI Chat</button>
        <button class="snav-btn" onclick="switchView('news',this)"><span class="material-icons-round">newspaper</span> News</button>
        <button class="snav-btn" onclick="switchView('quiz',this)"><span class="material-icons-round">quiz</span> Civic Quiz</button>
        <button class="snav-btn" onclick="openPanel('notifPanel')"><span class="material-icons-round">notifications</span> Notifications <?php if($unreadCount): ?><span class="snav-badge"><?= $unreadCount ?></span><?php endif; ?></button>
        <?php if($isAdmin): ?>
        <a href="admin.php" class="snav-btn"><span class="material-icons-round">admin_panel_settings</span> Admin</a>
        <?php endif; ?>
    </nav>

    <a href="logout.php" class="btn-logout">
        <span class="material-icons-round">logout</span> Sign Out
    </a>
</aside>

<!-- Main area -->
<main class="chat-main">

    <!-- Chat header -->
    <div class="chat-header">
        <div class="chat-header-left">
            <div class="chat-header-icon"><span class="material-icons-round">smart_toy</span></div>
            <div>
                <h2>VoterFlow AI</h2>
                <p><span class="status-dot"></span> Online &middot; Non-partisan &middot; Secure</p>
            </div>
        </div>
        <div class="chat-header-right">
            <?php if($userPrefs['state_province']): ?>
            <div class="region-chip" id="regionChip">
                <span class="material-icons-round">public</span>
                <?= htmlspecialchars($userPrefs['state_province']) ?>
            </div>
            <?php endif; ?>
            <?php if(!$userPrefs['state_province']): ?>
            <div class="region-chip" id="regionChip" style="display:none"></div>
            <?php endif; ?>
            <?php if($isAdmin): ?>
            <a href="admin.php" class="header-icon-btn mobile-hide" title="Admin Panel"><span class="material-icons-round">admin_panel_settings</span></a>
            <?php endif; ?>
            <button class="header-icon-btn mobile-hide" onclick="toggleDarkMode()" title="Toggle dark mode" id="darkBtn">
                <span class="material-icons-round"><?= $darkMode ? 'light_mode' : 'dark_mode' ?></span>
            </button>
            <button class="header-icon-btn mobile-hide notif-btn" onclick="openPanel('notifPanel')" title="Notifications">
                <span class="material-icons-round">notifications</span>
                <?php if($unreadCount): ?><span class="notif-badge"><?= $unreadCount ?></span><?php endif; ?>
            </button>
            <button class="header-icon-btn" onclick="exportChat()" title="Export chat">
                <span class="material-icons-round">download</span>
            </button>
            <button class="header-icon-btn" onclick="clearHistory()" title="Clear chat history">
                <span class="material-icons-round">delete_sweep</span>
            </button>
        </div>
    </div>

    <!-- Countdown banner -->
    <?php if($nextEvent): ?>
    <?php
        $daysLeft = (int)ceil((strtotime($nextEvent['event_date']) - time()) / 86400);
    ?>
    <div class="countdown-banner" id="countdownBanner">
        <span class="material-icons-round">event</span>
        <strong><?= htmlspecialchars($nextEvent['event_name']) ?></strong> in
        <span class="countdown-days"><?= $daysLeft ?> day<?= $daysLeft !== 1 ? 's' : '' ?></span>
        &mdash; <?= date('M d, Y', strtotime($nextEvent['event_date'])) ?>
        <button onclick="this.parentElement.style.display='none'" class="countdown-close"><span class="material-icons-round">close</span></button>
    </div>
    <?php endif; ?>

    <!-- Views -->
    <div class="view-container">

        <!-- Chat view -->
        <div class="view active" id="view-chat">
            <div class="chat-messages" id="chatMessages">
                <?php if (empty($chatHistory)): ?>
                <div class="message assistant">
                    <div class="msg-avatar"><span class="material-icons-round">smart_toy</span></div>
                    <div class="bubble">
                        👋 Hi <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>! I'm your VoterFlow guide.
                        <?php if (!$userPrefs['state_province']): ?>
                            To get started, what <strong>country</strong> are you in? You can set it in the sidebar.
                        <?php else: ?>
                            I'm ready to help you navigate the election process in <strong><?= htmlspecialchars($userPrefs['state_province']) ?></strong>. What would you like to know?
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($chatHistory as $idx => $msg): ?>
                <div class="message <?= $msg['role'] ?>" data-idx="<?= $idx ?>">
                    <?php if ($msg['role'] === 'assistant'): ?>
                    <div class="msg-avatar"><span class="material-icons-round">smart_toy</span></div>
                    <?php endif; ?>
                    <div class="bubble">
                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                        <?php if ($msg['role'] === 'assistant'): ?>
                        <div class="reaction-row">
                            <button class="react-btn" onclick="reactMsg(this,'up')" title="Helpful"><span class="material-icons-round">thumb_up</span></button>
                            <button class="react-btn" onclick="reactMsg(this,'down')" title="Not helpful"><span class="material-icons-round">thumb_down</span></button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-input-area">
                <div class="quick-prompts">
                    <button onclick="sendQuick('How do I register to vote?')"><span class="material-icons-round">how_to_reg</span> Register</button>
                    <button onclick="sendQuick('What are the upcoming election deadlines?')"><span class="material-icons-round">event</span> Deadlines</button>
                    <button onclick="sendQuick('How do I request a mail-in ballot?')"><span class="material-icons-round">mail</span> Mail ballot</button>
                    <button onclick="sendQuick('What should I bring to the polling station?')"><span class="material-icons-round">place</span> Polling tips</button>
                    <button onclick="sendQuick('What ID do I need to vote?')"><span class="material-icons-round">badge</span> Voter ID</button>
                </div>
                <div class="input-row">
                    <textarea id="userInput" placeholder="Ask me anything about elections..." rows="1" onkeydown="handleKey(event)"></textarea>
                    <button id="sendBtn" onclick="sendMessage()"><span class="material-icons-round">send</span></button>
                </div>
            </div>
        </div>

        <!-- News view -->
        <div class="view" id="view-news">
            <div class="panel-content">
                <div class="panel-header">
                    <span class="material-icons-round">newspaper</span>
                    <h3>Election News</h3>
                    <span class="panel-sub" id="newsCountryLabel"><?= htmlspecialchars($userPrefs['state_province'] ?? '') ?></span>
                </div>
                <div id="newsContainer" class="news-grid">
                    <div class="news-placeholder">
                        <span class="material-icons-round">newspaper</span>
                        <p><?= $userPrefs['state_province'] ? 'Loading news...' : 'Select a country to see election news' ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quiz view -->
        <div class="view" id="view-quiz">
            <div class="panel-content">
                <div class="panel-header">
                    <span class="material-icons-round">quiz</span>
                    <h3>Civic Knowledge Quiz</h3>
                    <?php if($quizBest): ?>
                    <span class="panel-sub">Best: <?= $quizBest['score'] ?>/<?= $quizBest['total'] ?></span>
                    <?php endif; ?>
                </div>
                <div id="quizContainer"></div>
            </div>
        </div>

    </div><!-- /view-container -->
</main>

<!-- Notifications panel -->
<div class="side-panel" id="notifPanel">
    <div class="side-panel-header">
        <h3><span class="material-icons-round">notifications</span> Notifications</h3>
        <button onclick="closePanel('notifPanel')"><span class="material-icons-round">close</span></button>
    </div>
    <div id="notifList" class="notif-list"><div class="notif-empty">Loading...</div></div>
    <button class="mark-read-btn" onclick="markNotifsRead()">Mark all as read</button>
</div>
<div class="panel-overlay" id="panelOverlay" onclick="closeAllPanels()"></div>

<!-- Clear History Modal -->
<div class="modal-overlay" id="clearModal">
    <div class="modal-box">
        <div class="modal-icon"><span class="material-icons-round">delete_sweep</span></div>
        <h3>Clear Chat History</h3>
        <p>This will permanently delete all your conversations. This action cannot be undone.</p>
        <div class="modal-actions">
            <button class="modal-btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="modal-btn-confirm" onclick="confirmClearHistory()">Yes, Clear All</button>
        </div>
    </div>
</div>

<!-- Modal removed as per request -->

<script>
    const userId   = <?= json_encode($_SESSION['user_id']) ?>;
    const userName = <?= json_encode($_SESSION['name']) ?>;
    const userCountry = <?= json_encode($userPrefs['state_province'] ?? '') ?>;
</script>
<script src="assets/app.js?v=<?= time() ?>"></script>
</body>
</html>
