<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
require_once 'config.php';

$pdo = getDBConnection();
$adminCheck = $pdo->prepare("SELECT is_admin FROM users WHERE user_id=?");
$adminCheck->execute([$_SESSION['user_id']]);
if (!$adminCheck->fetchColumn()) { header('Location: dashboard.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_event'])) {
        $pdo->prepare("DELETE FROM election_events WHERE event_id=?")->execute([(int)$_POST['delete_event']]);
    }
    if (isset($_POST['add_event'])) {
        $pdo->prepare("INSERT INTO election_events (region,event_name,event_date) VALUES (?,?,?)")
            ->execute([trim($_POST['region']), trim($_POST['event_name']), $_POST['event_date']]);
    }
    if (isset($_POST['toggle_admin'])) {
        $pdo->prepare("UPDATE users SET is_admin=NOT is_admin WHERE user_id=? AND user_id!=?")->execute([(int)$_POST['toggle_admin'], $_SESSION['user_id']]);
    }
    if (isset($_POST['update_credentials'])) {
        $fields=[]; $params=[];
        $newName=trim($_POST['new_name']??''); $newEmail=trim($_POST['new_email']??'');
        $newPass=$_POST['new_password']??''; $confirm=$_POST['confirm_password']??'';
        $credMsg='';
        if ($newName)  { $fields[]='name=?';  $params[]=$newName; }
        if ($newEmail) { $fields[]='email=?'; $params[]=$newEmail; }
        if ($newPass) {
            if (strlen($newPass)<8) $credMsg='error:Password must be at least 8 characters.';
            elseif ($newPass!==$confirm) $credMsg='error:Passwords do not match.';
            else { $fields[]='password=?'; $params[]=password_hash($newPass,PASSWORD_BCRYPT); }
        }
        if (!$credMsg && $fields) {
            $params[]=$_SESSION['user_id'];
            $pdo->prepare("UPDATE users SET ".implode(',',$fields)." WHERE user_id=?")->execute($params);
            if ($newName) $_SESSION['name']=$newName;
            if ($newEmail) $_SESSION['email']=$newEmail;
            $credMsg='success:Credentials updated successfully.';
        }
        $_SESSION['cred_msg']=$credMsg?:'error:Nothing to update.';
        header('Location: admin.php#account'); exit;
    }
    header('Location: admin.php'); exit;
}

$credMsg=$_SESSION['cred_msg']??''; unset($_SESSION['cred_msg']);
$me=$pdo->prepare("SELECT name,email FROM users WHERE user_id=?");
$me->execute([$_SESSION['user_id']]);
$me=$me->fetch(PDO::FETCH_ASSOC);

$users  = $pdo->query("SELECT user_id,name,email,oauth_provider,is_admin,created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$events = $pdo->query("SELECT * FROM election_events ORDER BY region,event_date")->fetchAll(PDO::FETCH_ASSOC);
$stats  = [
    'users'  => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'chats'  => $pdo->query("SELECT COUNT(*) FROM chat_history")->fetchColumn(),
    'events' => $pdo->query("SELECT COUNT(*) FROM election_events")->fetchColumn(),
    'voted'  => $pdo->query("SELECT COUNT(*) FROM user_milestones WHERE voted=1")->fetchColumn(),
];

// Analytics data
$usersPerDay = $pdo->query("SELECT DATE(created_at) as d, COUNT(*) as c FROM users GROUP BY DATE(created_at) ORDER BY d ASC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
$chatsPerDay = $pdo->query("SELECT DATE(created_at) as d, COUNT(*) as c FROM chat_history GROUP BY DATE(created_at) ORDER BY d ASC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
$topCountries= $pdo->query("SELECT state_province as country, COUNT(*) as c FROM user_preferences WHERE state_province IS NOT NULL AND state_province!='' GROUP BY state_province ORDER BY c DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$reactions   = $pdo->query("SELECT reaction, COUNT(*) as c FROM chat_reactions GROUP BY reaction")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoterFlow – Admin Panel</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#f1f5f9;color:#1e293b}
        .admin-layout{display:flex;min-height:100vh}
        .admin-sidebar{width:220px;min-width:220px;background:linear-gradient(180deg,#0f2444,#1a3a6b);color:#fff;display:flex;flex-direction:column}
        .admin-brand{display:flex;align-items:center;gap:.6rem;padding:1.4rem 1.2rem;font-size:1.05rem;font-weight:800;border-bottom:1px solid rgba(255,255,255,.08)}
        .admin-nav{display:flex;flex-direction:column;padding:.8rem 0;flex:1}
        .admin-nav a{display:flex;align-items:center;gap:.7rem;padding:.65rem 1.2rem;color:rgba(255,255,255,.7);text-decoration:none;font-size:.88rem;font-weight:500;transition:all .2s}
        .admin-nav a:hover,.admin-nav a.active{background:rgba(255,255,255,.1);color:#fff}
        .admin-nav a .material-icons-round{font-size:1.1rem}
        .admin-back{display:flex;align-items:center;gap:.5rem;margin:1rem;padding:.6rem;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.7);text-decoration:none;border-radius:10px;font-size:.82rem;transition:all .2s}
        .admin-back:hover{background:rgba(255,255,255,.15);color:#fff}
        .admin-main{flex:1;padding:1.5rem;overflow-y:auto;min-width:0}
        .admin-topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.5rem}
        .admin-topbar h1{font-size:1.3rem;font-weight:700}
        .admin-topbar p{font-size:.82rem;color:#64748b;margin-top:.2rem}
        .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem}
        .stat-card{background:#fff;border-radius:14px;padding:1.2rem;box-shadow:0 1px 4px rgba(0,0,0,.06);display:flex;align-items:center;gap:.9rem}
        .stat-icon{width:44px;height:44px;border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .stat-icon .material-icons-round{font-size:1.3rem;color:#fff}
        .stat-icon.blue{background:linear-gradient(135deg,#1e40af,#2563eb)}
        .stat-icon.green{background:linear-gradient(135deg,#059669,#10b981)}
        .stat-icon.purple{background:linear-gradient(135deg,#7c3aed,#8b5cf6)}
        .stat-icon.orange{background:linear-gradient(135deg,#d97706,#f59e0b)}
        .stat-val{font-size:1.5rem;font-weight:800;color:#0f172a}
        .stat-label{font-size:.75rem;color:#64748b;margin-top:2px}
        .admin-section{background:#fff;border-radius:14px;padding:1.3rem;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:1.2rem}
        .admin-section h2{font-size:.95rem;font-weight:700;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem}
        .admin-section h2 .material-icons-round{font-size:1.05rem;color:#2563eb}
        .charts-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.2rem}
        .chart-card{background:#fff;border-radius:14px;padding:1.2rem;box-shadow:0 1px 4px rgba(0,0,0,.06)}
        .chart-card h3{font-size:.88rem;font-weight:700;color:#374151;margin-bottom:.8rem;display:flex;align-items:center;gap:.4rem}
        .chart-card h3 .material-icons-round{font-size:1rem;color:#2563eb}
        canvas{max-height:180px}
        table{width:100%;border-collapse:collapse;font-size:.85rem}
        th{text-align:left;padding:.55rem .7rem;background:#f8fafc;color:#64748b;font-weight:600;font-size:.75rem;text-transform:uppercase;letter-spacing:.04em}
        td{padding:.65rem .7rem;border-top:1px solid #f1f5f9;vertical-align:middle}
        tr:hover td{background:#fafafa}
        .badge{display:inline-flex;align-items:center;padding:.18rem .55rem;border-radius:99px;font-size:.72rem;font-weight:600}
        .badge.admin{background:#eff6ff;color:#1e40af}
        .badge.user{background:#f1f5f9;color:#64748b}
        .badge.google{background:#fef3c7;color:#d97706}
        .badge.local{background:#f0fdf4;color:#16a34a}
        .btn-sm{padding:.3rem .7rem;border-radius:7px;border:none;cursor:pointer;font-size:.78rem;font-weight:600;font-family:inherit;transition:all .2s}
        .btn-danger{background:#fef2f2;color:#dc2626}
        .btn-danger:hover{background:#dc2626;color:#fff}
        .btn-primary-sm{background:#eff6ff;color:#1e40af}
        .btn-primary-sm:hover{background:#1e40af;color:#fff}
        .add-form{display:flex;gap:.7rem;flex-wrap:wrap;margin-top:1rem;padding-top:1rem;border-top:1px solid #f1f5f9}
        .add-form input,.add-form select,.add-form textarea{padding:.5rem .8rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.85rem;font-family:inherit;flex:1;min-width:130px}
        .add-form input:focus,.add-form select:focus,.add-form textarea:focus{outline:none;border-color:#2563eb}
        .btn-add{padding:.5rem 1.1rem;background:linear-gradient(135deg,#1e40af,#2563eb);color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-family:inherit;white-space:nowrap;font-size:.85rem}
        .broadcast-result{margin-top:.8rem;padding:.6rem .9rem;border-radius:8px;font-size:.85rem;font-weight:500;display:none}
        .broadcast-result.success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
        .broadcast-result.error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
        @media(max-width:900px){
            .stats-grid{grid-template-columns:repeat(2,1fr)}
            .charts-grid{grid-template-columns:1fr}
            .admin-sidebar{display:none}
        }
        @media(max-width:500px){
            .stats-grid{grid-template-columns:1fr 1fr}
            .admin-main{padding:1rem}
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-brand">🗳️ VoterFlow Admin</div>
        <nav class="admin-nav">
            <a href="#stats" class="active"><span class="material-icons-round">dashboard</span> Overview</a>
            <a href="#analytics"><span class="material-icons-round">bar_chart</span> Analytics</a>
            <a href="#broadcast"><span class="material-icons-round">campaign</span> Broadcast</a>
            <a href="#users"><span class="material-icons-round">people</span> Users</a>
            <a href="#events"><span class="material-icons-round">event</span> Events</a>
            <a href="#account"><span class="material-icons-round">manage_accounts</span> My Account</a>
        </nav>
        <a href="dashboard.php" class="admin-back"><span class="material-icons-round">arrow_back</span> Back to App</a>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar" id="stats">
            <div>
                <h1>Admin Panel</h1>
                <p>Welcome back, <?= htmlspecialchars($_SESSION['name']) ?></p>
            </div>
            <a href="dashboard.php" style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;color:#2563eb;text-decoration:none;font-weight:600">
                <span class="material-icons-round" style="font-size:1rem">arrow_back</span> Back to App
            </a>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon blue"><span class="material-icons-round">people</span></div><div><div class="stat-val"><?= $stats['users'] ?></div><div class="stat-label">Total Users</div></div></div>
            <div class="stat-card"><div class="stat-icon green"><span class="material-icons-round">chat</span></div><div><div class="stat-val"><?= $stats['chats'] ?></div><div class="stat-label">Chat Messages</div></div></div>
            <div class="stat-card"><div class="stat-icon purple"><span class="material-icons-round">event</span></div><div><div class="stat-val"><?= $stats['events'] ?></div><div class="stat-label">Election Events</div></div></div>
            <div class="stat-card"><div class="stat-icon orange"><span class="material-icons-round">how_to_vote</span></div><div><div class="stat-val"><?= $stats['voted'] ?></div><div class="stat-label">Users Voted</div></div></div>
        </div>

        <!-- Analytics Charts -->
        <div id="analytics">
            <div class="charts-grid">
                <div class="chart-card">
                    <h3><span class="material-icons-round">person_add</span> New Users (Last 7 Days)</h3>
                    <canvas id="usersChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3><span class="material-icons-round">chat</span> Chat Activity (Last 7 Days)</h3>
                    <canvas id="chatsChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3><span class="material-icons-round">public</span> Top Countries</h3>
                    <canvas id="countriesChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3><span class="material-icons-round">thumb_up</span> Chat Reactions</h3>
                    <canvas id="reactionsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Broadcast -->
        <div class="admin-section" id="broadcast">
            <h2><span class="material-icons-round">campaign</span> Broadcast Message</h2>
            <p style="font-size:.85rem;color:#64748b;margin-bottom:.8rem">Send a notification to all users.</p>
            <div style="display:flex;gap:.7rem;flex-wrap:wrap">
                <textarea id="broadcastMsg" placeholder="Type your announcement..." rows="2" style="flex:1;min-width:200px;padding:.6rem .9rem;border:1.5px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:.88rem;resize:vertical"></textarea>
                <button class="btn-add" onclick="sendBroadcast()" style="align-self:flex-end">Send to All</button>
            </div>
            <div class="broadcast-result" id="broadcastResult"></div>
        </div>

        <!-- Users -->
        <div class="admin-section" id="users">
            <h2><span class="material-icons-round">people</span> Users (<?= count($users) ?>)</h2>
            <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Auth</th><th>Role</th><th>Joined</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge <?= $u['oauth_provider'] ?>"><?= $u['oauth_provider'] ?></span></td>
                    <td><span class="badge <?= $u['is_admin'] ? 'admin' : 'user' ?>"><?= $u['is_admin'] ? 'Admin' : 'User' ?></span></td>
                    <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="toggle_admin" value="<?= $u['user_id'] ?>">
                            <button class="btn-sm btn-primary-sm"><?= $u['is_admin'] ? 'Revoke Admin' : 'Make Admin' ?></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <!-- Election Events -->
        <div class="admin-section" id="events">
            <h2><span class="material-icons-round">event</span> Election Events</h2>
            <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Region</th><th>Event</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($events as $e): ?>
                <tr>
                    <td><?= htmlspecialchars($e['region']) ?></td>
                    <td><?= htmlspecialchars($e['event_name']) ?></td>
                    <td><?= date('M d, Y', strtotime($e['event_date'])) ?></td>
                    <td>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="delete_event" value="<?= $e['event_id'] ?>">
                            <button class="btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <form method="POST" class="add-form">
                <input type="text" name="region" placeholder="Region / Country" required>
                <input type="text" name="event_name" placeholder="Event name" required>
                <input type="date" name="event_date" required>
                <button type="submit" name="add_event" class="btn-add">+ Add Event</button>
            </form>
        </div>

        <!-- My Account -->
        <div class="admin-section" id="account">
            <h2><span class="material-icons-round">manage_accounts</span> My Account</h2>
            <?php if ($credMsg): ?>
            <?php [$type,$text]=explode(':',$credMsg,2); ?>
            <div style="padding:.65rem .9rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem;font-weight:500;
                background:<?= $type==='success'?'#f0fdf4':'#fef2f2' ?>;
                color:<?= $type==='success'?'#16a34a':'#dc2626' ?>;
                border:1px solid <?= $type==='success'?'#bbf7d0':'#fecaca' ?>">
                <?= htmlspecialchars($text) ?>
            </div>
            <?php endif; ?>
            <form method="POST" class="add-form" style="flex-direction:column;gap:1rem">
                <input type="hidden" name="update_credentials" value="1">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem">
                    <div>
                        <label style="font-size:.75rem;font-weight:600;color:#64748b;display:block;margin-bottom:.3rem">Full Name</label>
                        <input type="text" name="new_name" value="<?= htmlspecialchars($me['name']) ?>">
                    </div>
                    <div>
                        <label style="font-size:.75rem;font-weight:600;color:#64748b;display:block;margin-bottom:.3rem">Email</label>
                        <input type="email" name="new_email" value="<?= htmlspecialchars($me['email']) ?>">
                    </div>
                    <div>
                        <label style="font-size:.75rem;font-weight:600;color:#64748b;display:block;margin-bottom:.3rem">New Password</label>
                        <input type="password" name="new_password" placeholder="Min. 8 characters">
                    </div>
                    <div>
                        <label style="font-size:.75rem;font-weight:600;color:#64748b;display:block;margin-bottom:.3rem">Confirm Password</label>
                        <input type="password" name="confirm_password" placeholder="Repeat new password">
                    </div>
                </div>
                <div><button type="submit" class="btn-add">✓ Save Changes</button></div>
            </form>
        </div>
    </main>
</div>

<script>
const usersData    = <?= json_encode($usersPerDay) ?>;
const chatsData    = <?= json_encode($chatsPerDay) ?>;
const countriesData= <?= json_encode($topCountries) ?>;
const reactionsData= <?= json_encode($reactions) ?>;

const chartDefaults = { responsive:true, plugins:{ legend:{ display:false } }, scales:{ x:{ grid:{display:false}, ticks:{font:{size:11}} }, y:{ beginAtZero:true, ticks:{font:{size:11}, precision:0} } } };

new Chart(document.getElementById('usersChart'), {
    type:'bar',
    data:{ labels: usersData.map(r=>r.d), datasets:[{ data: usersData.map(r=>r.c), backgroundColor:'rgba(37,99,235,.7)', borderRadius:6 }] },
    options: chartDefaults
});
new Chart(document.getElementById('chatsChart'), {
    type:'line',
    data:{ labels: chatsData.map(r=>r.d), datasets:[{ data: chatsData.map(r=>r.c), borderColor:'#10b981', backgroundColor:'rgba(16,185,129,.1)', fill:true, tension:.4, pointRadius:4 }] },
    options: chartDefaults
});
new Chart(document.getElementById('countriesChart'), {
    type:'bar',
    data:{ labels: countriesData.map(r=>r.country), datasets:[{ data: countriesData.map(r=>r.c), backgroundColor:'rgba(124,58,237,.7)', borderRadius:6 }] },
    options:{ ...chartDefaults, indexAxis:'y' }
});

const rMap = Object.fromEntries(reactionsData.map(r=>[r.reaction, r.c]));
new Chart(document.getElementById('reactionsChart'), {
    type:'doughnut',
    data:{
        labels:['👍 Helpful','👎 Not helpful'],
        datasets:[{ data:[rMap.up||0, rMap.down||0], backgroundColor:['#10b981','#ef4444'], borderWidth:0 }]
    },
    options:{ responsive:true, plugins:{ legend:{ position:'bottom', labels:{ font:{size:12} } } } }
});

async function sendBroadcast() {
    const msg = document.getElementById('broadcastMsg').value.trim();
    const res = document.getElementById('broadcastResult');
    if (!msg) return;
    const r = await fetch('api/broadcast.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({message:msg}) });
    const d = await r.json();
    res.style.display = 'block';
    if (d.success) { res.className='broadcast-result success'; res.textContent=`✓ Sent to ${d.sent} users`; document.getElementById('broadcastMsg').value=''; }
    else { res.className='broadcast-result error'; res.textContent='Error: '+(d.error||'Unknown'); }
}
</script>
</body>
</html>
