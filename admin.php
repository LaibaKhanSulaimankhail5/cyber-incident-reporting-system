<?php
session_start();
if (!isset($_SESSION['user_id'])||$_SESSION['role']!=='admin') { header('Location: index.php'); exit; }
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$user_name=$_SESSION['user_name']; $user_id=$_SESSION['user_id'];
require_once __DIR__.'/config/db.php';
$gs=$pdo->query("SELECT COUNT(*) t, SUM(status='open') o, SUM(status='investigating') inv, SUM(status='resolved') res, SUM(status='closed') c, SUM(severity='critical') crit, SUM(severity='high') high, (SELECT COUNT(*) FROM alerts WHERE is_read=FALSE) ua FROM incidents")->fetch();
$gs_t   = htmlspecialchars($gs['t'] ?? 0);
$gs_o   = htmlspecialchars($gs['o'] ?? 0);
$gs_inv = htmlspecialchars($gs['inv'] ?? 0);
$gs_res = htmlspecialchars($gs['res'] ?? 0);
$gs_c   = htmlspecialchars($gs['c'] ?? 0);
$gs_crit= htmlspecialchars($gs['crit'] ?? 0);
$gs_high= htmlspecialchars($gs['high'] ?? 0);
$gs_ua  = htmlspecialchars($gs['ua'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin SOC — Cyber Incident System</title>
<link rel="stylesheet" href="css/style.css">
<style>
.dash-layout { display:grid;grid-template-columns:240px 1fr;min-height:calc(100vh - 64px); }
.sidebar { background:var(--bg-card);border-right:1px solid var(--border);padding:20px 14px;display:flex;flex-direction:column;gap:6px; }
.sidebar-section { font-size:10px;font-family:var(--font-head);font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-muted);padding:4px 12px;margin-top:10px; }
.sidebar-link { display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:var(--radius-md);color:var(--text-secondary);font-size:13px;font-weight:500;cursor:pointer;transition:all 0.2s;border:1px solid transparent;text-decoration:none; }
.sidebar-link:hover { color:var(--text-primary);background:var(--bg-hover); }
.sidebar-link.active { color:var(--neon-cyan);background:rgba(0,212,255,0.08);border-color:var(--border-cyan); }
.main-content { padding:24px;overflow-y:auto; }
.stats-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin-bottom:24px; }
.stat-card { background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;position:relative;overflow:hidden;transition:all 0.3s;cursor:default; }
.stat-card::after { content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:var(--acc,var(--neon-cyan));transform:scaleX(0);transform-origin:left;transition:transform 0.3s; }
.stat-card:hover { border-color:rgba(255,255,255,0.1);transform:translateY(-2px); }
.stat-card.active { border-color:var(--acc); background:rgba(255,255,255,0.03); }
.stat-card:hover::after, .stat-card.active::after { transform:scaleX(1); }
.s-icon { font-size:26px;margin-bottom:8px; }
.s-val { font-size:32px;font-weight:800;font-family:var(--font-head); }
.s-lbl { font-size:10px;color:var(--text-muted);font-family:var(--font-head);font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-top:2px; }
.main-grid { display:grid;grid-template-columns:320px 1fr;gap:20px; }
.panel { background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden; }
.panel-header { padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:rgba(0,0,0,0.2); }
.panel-header h3 { font-size:14px;font-family:var(--font-head);font-weight:700;display:flex;align-items:center;gap:8px; }
@media(max-width:768px){.dash-layout{grid-template-columns:1fr}.sidebar{display:none}}
.btn-text-red { background:transparent; border:none; color:var(--red); cursor:pointer; font-weight:600; font-family:var(--font-head); transition:all 0.2s; opacity:0.8; }
.btn-text-red:hover { opacity:1; text-shadow:0 0 8px rgba(255,71,87,0.4); transform: scale(1.05); }
.delete-btn { font-size: 11px; padding: 6px 12px; border-radius: 4px; border: 1px solid rgba(255,71,87,0.3) !important; background: rgba(255,71,87,0.05); display: inline-flex; align-items: center; gap: 4px; }
.delete-btn:hover { background: rgba(255,71,87,0.1) !important; border-color: var(--red) !important; }
</style>
</head>
<body>
<nav class="navbar">
  <div class="nav-logo"><div class="logo-mark">🛡️</div>Cyber<span> Incident System</span></div>
  <div class="nav-links">
    <a href="admin.php" class="nav-link active">🖥️ SOC</a>
    <a href="report.php" class="nav-link">⚠️ Reports</a>
    <a href="incident.php" class="nav-link">🔍 Investigate</a>
  </div>
  <div class="nav-right">
    <div class="nav-icon-btn">🔔<?php if((int)$gs['ua']>0):?><span class="notif-dot"></span><?php endif;?></div>
    <button class="btn-outline-cyan" onclick="loadAll()" style="font-size:11px;padding:7px 14px;">↻ Refresh</button>
    <div class="user-avatar"><?= strtoupper(substr($user_name,0,1)) ?></div>
    <a href="logout.php" style="font-size:12px;color:var(--text-muted);padding:0 8px;">Sign Out</a>
  </div>
</nav>

<div class="dash-layout">
  <aside class="sidebar">
    <div class="sidebar-section">Security Ops</div>
    <a href="admin.php"   class="sidebar-link active"><span>🖥️</span> Command Center</a>
    <a href="incident.php" class="sidebar-link"><span>🔍</span> Investigations</a>
    <a href="report.php"  class="sidebar-link"><span>⚠️</span> Reports</a>
    <div class="sidebar-section">Administration</div>
    <a href="#user-management" class="sidebar-link"><span>👥</span> User Management</a>
    <a href="2fa.php" class="sidebar-link"><span>🔐</span> 2FA Config</a>
    <div style="margin-top:auto;padding-top:20px;border-top:1px solid var(--border);">
      <div style="padding:10px;display:flex;align-items:center;gap:10px;">
        <div class="user-avatar" style="width:32px;height:32px;font-size:12px;"><?= strtoupper(substr($user_name,0,1)) ?></div>
        <div><div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($user_name) ?></div>
        <div style="font-size:11px;color:var(--neon-cyan);">Administrator</div></div>
      </div>
      <a href="logout.php" class="sidebar-link"><span>🚪</span> Sign Out</a>
    </div>
  </aside>

  <main class="main-content">
    <div style="margin-bottom:20px;">
      <h1 style="font-size:22px;font-family:var(--font-head);">Security Operations Center <span style="font-size:14px;color:var(--neon-cyan);margin-left:10px;font-weight:400;"><span class="notif-dot" style="position:relative;display:inline-block;width:7px;height:7px;top:0;right:0;"></span> LIVE</span></h1>
      <div style="font-size:13px;color:var(--text-muted);margin-top:2px;"><?= date('D, d M Y H:i:s') ?> · Auto-refresh: 15s</div>
    </div>

    <div class="stats-grid stagger">
      <div class="stat-card" style="--acc:var(--neon-cyan)" onclick="filterInc('all','all',this)">
        <div class="s-icon">📊</div>
        <div class="s-val" id="stat-total" style="color:var(--neon-cyan)"><?=$gs_t?></div>
        <div class="s-lbl">Total Incidents</div>
      </div>
      <div class="stat-card" style="--acc:var(--red)" onclick="filterInc('open','all',this)">
        <div class="s-icon">🔴</div>
        <div class="s-val" id="stat-open" style="color:var(--red)"><?=$gs_o?></div>
        <div class="s-lbl">Open</div>
      </div>
      <div class="stat-card" style="--acc:var(--yellow)" onclick="filterInc('investigating','all',this)">
        <div class="s-icon">🔍</div>
        <div class="s-val" id="stat-investigating" style="color:var(--yellow)"><?=$gs_inv?></div>
        <div class="s-lbl">Investigating</div>
      </div>
      <div class="stat-card" style="--acc:var(--green)" onclick="filterInc('resolved','all',this)">
        <div class="s-icon">✅</div>
        <div class="s-val" id="stat-resolved" style="color:var(--green)"><?=$gs_res?></div>
        <div class="s-lbl">Resolved</div>
      </div>
      <div class="stat-card" style="--acc:var(--orange)" onclick="filterInc('all','all',this,'high')">
        <div class="s-icon">🔥</div>
        <div class="s-val" id="stat-high" style="color:var(--orange)"><?=$gs_high?></div>
        <div class="s-lbl">High</div>
      </div>
      <div class="stat-card" style="--acc:var(--red)" onclick="filterInc('all','all',this,'critical')">
        <div class="s-icon">☠️</div>
        <div class="s-val" id="stat-critical" style="color:var(--red)"><?=$gs_crit?></div>
        <div class="s-lbl">Critical</div>
      </div>
      <div class="stat-card" style="--acc:var(--neon-purple)" onclick="filterInc('closed','all',this)">
        <div class="s-icon">🔒</div>
        <div class="s-val" id="stat-closed" style="color:var(--neon-purple)"><?=$gs_c?></div>
        <div class="s-lbl">Closed</div>
      </div>
    </div>

    <div class="main-grid">
      <div class="panel">
        <div class="panel-header">
          <h3>🔔 Live Threat Alerts</h3>
          <span class="badge badge-critical" id="alert-badge" style="display:none;"></span>
        </div>
        <div id="feed-list"><div class="empty-state">Connecting...</div></div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <h3>📋 All Incident Reports</h3>
        </div>
        <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;gap:6px;flex-wrap:wrap;background:rgba(0,0,0,0.1);">
          <button class="filter-pill active" onclick="filterInc('all','all',this)">All</button>
          <button class="filter-pill" onclick="filterInc('open','all',this)">Open</button>
          <button class="filter-pill" onclick="filterInc('investigating','all',this)">Investigating</button>
          <button class="filter-pill" onclick="filterInc('resolved','all',this)">Resolved</button>
          <button class="filter-pill" onclick="filterInc('all','phishing',this)">Phishing</button>
          <button class="filter-pill" onclick="filterInc('all','malware',this)">Malware</button>
        </div>
        <div id="inc-list"><div class="empty-state">Loading...</div></div>
      </div>
    </div><!-- /.main-grid -->

    <div class="panel" id="user-management" style="margin-top:24px;">
      <div class="panel-header">
        <h3>👥 User Management</h3>
        <button class="btn-outline-cyan" onclick="loadUsers()" style="font-size:11px;padding:7px 14px;">Refresh</button>
      </div>
      <div id="user-table"><div class="empty-state">Loading users...</div></div>
    </div>

  </main>
</div>

<script src="js/main.js"></script>
<script>window.CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?>';</script>
<script>
  window.CURRENT_USER = <?= (int)$user_id ?>;
  window.cS = 'all'; window.cT = 'all'; window.cSev = 'all';

  document.addEventListener('DOMContentLoaded', function() {
    loadAll();
    loadUsers();
    initRealTime();
    
    // Cross-tab sync
    window.addEventListener('storage', (e) => {
      if (e.key === 'incidentUpdated') {
        loadAll();
        loadUsers();
      }
    });
  });
</script>
</body>
</html>