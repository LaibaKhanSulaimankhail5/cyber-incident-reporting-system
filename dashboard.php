<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
if ($_SESSION['role']==='admin')  { header('Location: admin.php'); exit; }
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$user_id=(int)$_SESSION['user_id']; $user_name=$_SESSION['user_name'];
require_once __DIR__.'/config/db.php';
$s=$pdo->prepare("SELECT COUNT(*) t,SUM(status='open') o,SUM(status='investigating') inv,SUM(status='resolved') res FROM incidents WHERE user_id=?");
$s->execute([$user_id]); $st=$s->fetch();

$st_t   = htmlspecialchars($st['t'] ?? 0);
$st_o   = htmlspecialchars($st['o'] ?? 0);
$st_inv = htmlspecialchars($st['inv'] ?? 0);
$st_res = htmlspecialchars($st['res'] ?? 0);

$alerts_count=(int)$pdo->query("SELECT COUNT(*) FROM alerts WHERE is_read=FALSE")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — Cyber Incident System</title>
<link rel="stylesheet" href="css/style.css">
<style>
.dash-layout { display:grid; grid-template-columns:260px 1fr; min-height:calc(100vh - 64px); }

/* SIDEBAR */
.sidebar {
  background:var(--bg-card); border-right:1px solid var(--border);
  padding:24px 16px; display:flex; flex-direction:column; gap:8px;
}
.sidebar-section { font-size:10px;font-family:var(--font-head);font-weight:700;
  letter-spacing:0.1em;text-transform:uppercase;color:var(--text-muted);
  padding:4px 12px;margin-top:12px; }
.sidebar-link {
  display:flex;align-items:center;gap:10px;padding:10px 14px;
  border-radius:var(--radius-md);color:var(--text-secondary);
  font-size:13px;font-weight:500;cursor:pointer;transition:all 0.2s;
  border:1px solid transparent; text-decoration:none;
}
.sidebar-link:hover { color:var(--text-primary);background:var(--bg-hover); }
.sidebar-link.active { color:var(--neon-cyan);background:rgba(0,212,255,0.08);border-color:var(--border-cyan); }
.sidebar-link .icon { font-size:16px;width:20px;text-align:center; }

/* Quick stats in sidebar */
.sidebar-stat {
  background:var(--bg-surface);border:1px solid var(--border);
  border-radius:var(--radius-md);padding:12px 14px;margin:4px 0;
}
.sidebar-stat .s-label { font-size:10px;font-family:var(--font-head);font-weight:700;
  text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted); }
.sidebar-stat .s-val { font-size:26px;font-weight:800;font-family:var(--font-head);margin-top:2px; }
.s-val.red { color:var(--red); } .s-val.cyan { color:var(--neon-cyan); } .s-val.yellow { color:var(--yellow); }

/* MAIN CONTENT */
.main-content { padding:24px; overflow-y:auto; }

.content-grid { display:grid; grid-template-columns:1fr 340px; gap:20px; }

/* Stats cards row */
.stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:20px; }
.stat-card {
  background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-lg);
  padding:20px; position:relative; overflow:hidden; transition:all 0.3s; cursor:default;
}
.stat-card::after { content:'';position:absolute;bottom:0;left:0;right:0;height:2px;
  background:var(--card-accent,var(--neon-cyan));transform:scaleX(0);transform-origin:left;transition:transform 0.3s; }
.stat-card:hover { border-color:rgba(255,255,255,0.12);transform:translateY(-2px); }
.stat-card:hover::after { transform:scaleX(1); }
.card-icon-big { font-size:32px;margin-bottom:10px; }
.card-val { font-size:36px;font-weight:800;font-family:var(--font-head);color:var(--text-primary); }
.card-lbl { font-size:11px;color:var(--text-muted);font-family:var(--font-head);font-weight:700;
  text-transform:uppercase;letter-spacing:0.08em;margin-top:2px; }
.card-sub { font-size:11px;color:var(--text-muted);margin-top:6px; }

/* Report form card */
.form-card { background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden; }
.form-card-header {
  padding:18px 22px;border-bottom:1px solid var(--border);
  background:rgba(0,0,0,0.2);
  display:flex;align-items:center;justify-content:space-between;
}
.form-card-header h2 { font-size:16px;font-family:var(--font-head);font-weight:700; }
.ai-active-badge {
  display:flex;align-items:center;gap:6px;
  font-size:11px;font-family:var(--font-head);font-weight:700;
  color:var(--neon-cyan);
  background:rgba(0,212,255,0.08);border:1px solid var(--border-cyan);
  border-radius:100px;padding:4px 10px;
}
.ai-active-badge::before { content:'';width:6px;height:6px;background:var(--neon-cyan);border-radius:50%;animation:pulse-glow 1.5s infinite; }
.form-card-body { padding:22px; }

/* Right column */
.right-col { display:flex;flex-direction:column;gap:16px; }

/* Robot floating */
.robot-float-wrap {
  background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);
  padding:24px;text-align:center;position:relative;overflow:hidden;
}
.robot-float-wrap::before { content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse at 50% 50%,rgba(0,212,255,0.05) 0%,transparent 70%); }
.mini-robot { width:100px;height:120px;margin:0 auto;animation:float 4s ease-in-out infinite;position:relative;z-index:1; }
.mini-ring {
  position:absolute;border-radius:50%;border:1px solid;
  top:50%;left:50%;
}
.mr1 { width:140px;height:140px;margin:-70px 0 0 -70px;border-color:rgba(0,212,255,0.25);animation:rotate-ring 8s linear infinite; }
.mr2 { width:180px;height:180px;margin:-90px 0 0 -90px;border-color:rgba(107,93,255,0.15);border-style:dashed;animation:rotate-ring 14s linear infinite reverse; }
.robot-msg { font-size:12px;color:var(--text-muted);margin-top:12px;line-height:1.6;position:relative;z-index:1; }

/* Live feed */
.feed-card { background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;flex:1; }
.feed-header { padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
.feed-header h3 { font-size:14px;font-family:var(--font-head);font-weight:700;display:flex;align-items:center;gap:8px; }
.feed-body { max-height:300px;overflow-y:auto; }

/* Quick actions */
.qa-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:8px; }

/* Recent reports table */
.table-card { background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-top:20px; }
.table-header { padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
.table-header h3 { font-size:14px;font-family:var(--font-head);font-weight:700; }
table { width:100%;border-collapse:collapse;font-size:13px; }
thead th { padding:10px 14px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;
  letter-spacing:0.08em;color:var(--text-muted);border-bottom:1px solid var(--border);
  font-family:var(--font-head);background:rgba(0,0,0,0.2); }
tbody td { padding:13px 14px;border-bottom:1px solid rgba(255,255,255,0.04);color:var(--text-secondary);vertical-align:middle; }
tbody tr:hover td { background:var(--bg-hover); }
tbody tr:last-child td { border-bottom:none; }

/* Progress bar inline */
.prog-mini { height:4px;background:rgba(255,255,255,0.06);border-radius:2px;margin-top:4px;overflow:hidden;width:100px; }
.prog-mini-fill { height:100%;border-radius:2px;background:linear-gradient(90deg,var(--neon-cyan),var(--neon-purple)); }

@media(max-width:1024px) { .content-grid { grid-template-columns:1fr; } }
@media(max-width:768px) { .dash-layout { grid-template-columns:1fr; } .sidebar { display:none; } .stats-row { grid-template-columns:repeat(2,1fr); } }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="nav-logo">
    <div class="logo-mark">🛡️</div>
    Cyber<span> Incident System</span>
  </div>
  <div class="nav-links">
    <a href="dashboard.php" class="nav-link active">🏠 Dashboard</a>
    <a href="report.php"    class="nav-link">⚠️ Report</a>
    <a href="incident.php"  class="nav-link">🔍 Investigate</a>
    <a href="2fa.php"       class="nav-link">🔐 2FA Setup</a>
  </div>
  <div class="nav-right">
    <div class="nav-icon-btn" title="Notifications">
      🔔
      <?php if((int)$alerts_count>0): ?><span class="notif-dot"></span><?php endif; ?>
    </div>
    <div class="nav-icon-btn">⚙️</div>
    <div class="user-avatar" title="<?= htmlspecialchars($user_name) ?>">
      <?= strtoupper(substr($user_name,0,1)) ?>
    </div>
    <a href="logout.php" style="font-size:12px;color:var(--text-muted);padding:0 8px;">Sign Out</a>
  </div>
</nav>

<div class="dash-layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-section">Navigation</div>
    <a href="dashboard.php" class="sidebar-link active"><span class="icon">🏠</span> Dashboard</a>
    <a href="report.php"    class="sidebar-link"><span class="icon">⚠️</span> Report Incident</a>
    <a href="incident.php"  class="sidebar-link"><span class="icon">🔍</span> Investigations</a>
    <a href="2fa.php"       class="sidebar-link"><span class="icon">🔐</span> 2FA Setup</a>

    <div class="sidebar-section">Quick Stats</div>
    <div class="sidebar-stat">
      <div class="s-label">Active Threats</div>
      <div class="s-val red"><?= (int)$alerts_count ?></div>
    </div>
    <div class="sidebar-stat">
      <div class="s-label">Reports Today</div>
      <div class="s-val cyan"><?=$st_t?></div>
    </div>
    <div class="sidebar-stat">
      <div class="s-label">Pending Review</div>
      <div class="s-val yellow"><?=$st_inv?></div>
    </div>

    <div style="margin-top:auto;padding-top:20px;border-top:1px solid var(--border);">
      <div style="display:flex;align-items:center;gap:10px;padding:10px;">
        <div class="user-avatar" style="width:32px;height:32px;font-size:12px;"><?= strtoupper(substr($user_name,0,1)) ?></div>
        <div>
          <div style="font-size:13px;font-weight:600;color:var(--text-primary);"><?= htmlspecialchars($user_name) ?></div>
          <div style="font-size:11px;color:var(--text-muted);">Employee</div>
        </div>
      </div>
      <a href="logout.php" class="sidebar-link" style="margin-top:4px;"><span class="icon">🚪</span> Sign Out</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main-content">

    <!-- Page header -->
    <div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;">
      <div>
        <h1 style="font-size:22px;font-family:var(--font-head);">Security Dashboard</h1>
        <div style="font-size:13px;color:var(--text-muted);margin-top:2px;"><?= date('l, d M Y') ?> &nbsp;·&nbsp; Welcome back, <?= htmlspecialchars($user_name) ?></div>
      </div>
      <a href="report.php" class="btn-gradient" style="width:auto;padding:10px 22px;font-size:13px;"><span>+ New Report</span></a>
    </div>

    <!-- Top stats -->
    <div class="stats-row stagger">
      <div class="stat-card" style="--card-accent:var(--neon-cyan)">
        <div class="card-icon-big">📊</div>
        <div class="card-val" id="dashboard-stat-total"><?=$st_t?></div>
        <div class="card-lbl">Total Reports</div>
        <div class="card-sub">Your incidents</div>
      </div>
      <div class="stat-card" style="--card-accent:var(--orange)">
        <div class="card-icon-big">🔄</div>
        <div class="card-val" id="dashboard-stat-inv"><?=$st_inv?></div>
        <div class="card-lbl">Under Investigation</div>
        <div class="card-sub">Pending review</div>
      </div>
      <div class="stat-card" style="--card-accent:var(--green)">
        <div class="card-icon-big">✅</div>
        <div class="card-val" id="dashboard-stat-res"><?=$st_res?></div>
        <div class="card-lbl">Resolved</div>
        <div class="card-sub">This month</div>
      </div>
    </div>

    <!-- Content grid -->
    <div class="content-grid">

      <!-- LEFT: Report form + table -->
      <div>

        <!-- Incident Report Form -->
        <div class="form-card card-glow" style="margin-bottom:20px;">
          <div class="form-card-header">
            <h2>⚠️ Report Cyber Incident</h2>
            <div class="ai-active-badge">AI Analysis Active</div>
          </div>
          <div class="form-card-body">

            <div class="form-group">
              <label class="form-label">Incident Title *</label>
              <input class="form-input" type="text" id="title" placeholder="e.g. Suspicious login from unknown IP">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
              <div class="form-group">
                <label class="form-label">Incident Type *</label>
                <select class="form-input" id="incident_type">
                  <option value="">-- Select Type --</option>
                  <option value="phishing">🎣 Phishing Attack</option>
                  <option value="malware">🦠 Malware / Virus</option>
                  <option value="unauthorized_access">🔓 Unauthorized Access</option>
                  <option value="data_breach">💾 Data Breach</option>
                  <option value="other">📌 Other</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Severity Level *</label>
                <div class="sev-grid">
                  <button class="sev-btn" data-val="low"      onclick="setSeverity('low')">🟢 Low</button>
                  <button class="sev-btn" data-val="medium"   onclick="setSeverity('medium')">🟡 Med</button>
                  <button class="sev-btn" data-val="high"     onclick="setSeverity('high')">🟠 High</button>
                  <button class="sev-btn" data-val="critical" onclick="setSeverity('critical')">🔴 Crit</button>
                </div>
                <input type="hidden" id="severity-hidden" value="medium">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Description *</label>
              <textarea class="form-input" id="description" style="min-height:90px;"
                        placeholder="Describe what happened, when, and any actions taken..."></textarea>
            </div>

            <button id="submit-btn" class="btn-gradient" onclick="submitIncident()">
              <span>Submit Incident Report &rarr;</span>
            </button>

            <div id="form-msg" class="alert-msg"></div>

            <div id="ai-result-box" class="ai-box" style="margin-top:14px;">
              <div class="ai-label">AI Threat Analysis</div>
              <div class="ai-content" id="ai-result-text"></div>
            </div>

          </div>
        </div>

        <!-- Quick Actions -->
        <div style="margin-bottom:20px;">
          <div style="font-size:13px;font-family:var(--font-head);font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:10px;">Quick Actions</div>
          <div class="qa-grid">
            <div class="quick-action" onclick="loadIncidents('inc-list','all','all')"><div class="qa-icon">📋</div>View Reports</div>
            <div class="quick-action" onclick="location.href='incident.php'"><div class="qa-icon">🔍</div>Investigate</div>
            <div class="quick-action" onclick="location.href='report.php'" style="border-color:rgba(255,71,87,0.3);color:var(--red);">
              <div class="qa-icon">🚨</div>Urgent Help
            </div>
          </div>
        </div>

        <!-- Recent reports table -->
        <div class="table-card">
          <div class="table-header">
            <h3>📋 Recent Reports</h3>
            <div class="filter-bar">
              <button class="filter-pill active" onclick="filterTable('all',this)">All</button>
              <button class="filter-pill" onclick="filterTable('open',this)">Open</button>
              <button class="filter-pill" onclick="filterTable('investigating',this)">Investigating</button>
              <button class="filter-pill" onclick="filterTable('resolved',this)">Resolved</button>
            </div>
          </div>
          <div id="inc-list"></div>
        </div>

      </div>

      <!-- RIGHT COL -->
      <div class="right-col">

        <!-- Robot illustration -->
        <div class="robot-float-wrap">
          <div style="position:relative;height:180px;display:flex;align-items:center;justify-content:center;">
            <div class="mr1 mini-ring"></div>
            <div class="mr2 mini-ring"></div>
            <svg class="mini-robot" viewBox="0 0 100 120" fill="none" xmlns="http://www.w3.org/2000/svg"
                 style="filter:drop-shadow(0 0 16px rgba(0,212,255,0.4))">
              <line x1="50" y1="8" x2="50" y2="20" stroke="#00d4ff" stroke-width="1.5"/>
              <circle cx="50" cy="5" r="4" fill="#00d4ff"><animate attributeName="opacity" values="1;0.2;1" dur="1.5s" repeatCount="indefinite"/></circle>
              <rect x="20" y="20" width="60" height="44" rx="10" fill="#0d1231" stroke="#00d4ff" stroke-width="1"/>
              <ellipse cx="36" cy="38" rx="8" ry="7" fill="#00d4ff"><animate attributeName="opacity" values="1;0.5;1" dur="2s" repeatCount="indefinite"/></ellipse>
              <ellipse cx="64" cy="38" rx="8" ry="7" fill="#6b5dff"><animate attributeName="opacity" values="1;0.5;1" dur="2s" begin="0.5s" repeatCount="indefinite"/></ellipse>
              <rect x="34" y="52" width="32" height="6" rx="3" fill="#070b1d" stroke="#00d4ff" stroke-width="0.8"/>
              <rect x="42" y="64" width="16" height="6" rx="2" fill="#0d1231" stroke="#6b5dff" stroke-width="0.8"/>
              <rect x="12" y="70" width="76" height="40" rx="12" fill="#0d1231" stroke="#6b5dff" stroke-width="1"/>
              <rect x="28" y="80" width="44" height="22" rx="6" fill="#070b1d" stroke="#00d4ff" stroke-width="0.8"/>
              <circle cx="38" cy="91" r="4" fill="#00d4ff"><animate attributeName="opacity" values="1;0.2;1" dur="1s" repeatCount="indefinite"/></circle>
              <circle cx="50" cy="91" r="4" fill="#6b5dff"><animate attributeName="opacity" values="1;0.2;1" dur="1s" begin="0.33s" repeatCount="indefinite"/></circle>
              <circle cx="62" cy="91" r="4" fill="#00ff88"><animate attributeName="opacity" values="1;0.2;1" dur="1s" begin="0.66s" repeatCount="indefinite"/></circle>
              <rect x="0" y="74" width="12" height="26" rx="6" fill="#0d1231" stroke="#00d4ff" stroke-width="1"/>
              <rect x="88" y="74" width="12" height="26" rx="6" fill="#0d1231" stroke="#00d4ff" stroke-width="1"/>
            </svg>
          </div>
          <div style="font-size:14px;font-family:var(--font-head);font-weight:700;color:var(--neon-cyan);">AI Security Assistant</div>
          <div class="robot-msg">I analyze every incident you report and provide instant threat assessment with recommended response actions.</div>
        </div>

        <!-- Live Threat Feed -->
        <div class="feed-card">
          <div class="feed-header">
            <h3><span class="notif-dot" style="position:relative;display:inline-block;width:7px;height:7px;top:0;right:0;margin-right:6px;"></span>Live Threat Feed</h3>
            <span class="badge badge-critical" id="alert-badge" style="display:none;"></span>
          </div>
          <div class="feed-body" id="feed-list">
            <div class="empty-state">Loading feed...</div>
          </div>
        </div>

      </div>
    </div>

  </main>
</div>

<!-- Floating Action Button (mobile) -->
<button class="fab" onclick="location.href='report.php'" title="Report Incident">⚠️</button>

<script src="js/main.js"></script>
<script>window.CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?>';</script>
<script>
  window.CURRENT_USER = <?= $user_id ?>;
  let cFilter = 'all';

  document.addEventListener('DOMContentLoaded', function(){
    setSeverity('medium');
    loadFeed();
    loadIncidents('inc-list','all','all');
    updateStats();

    // Auto-refresh every 15 seconds (incidents + feed + stats)
    setInterval(() => {
      loadFeed();
      loadIncidents('inc-list', cFilter, 'all');
      updateStats();
    }, 15000);

    // Listen for cross-tab updates (same-browser, different tab)
    window.addEventListener('storage', (e) => {
      if (e.key === 'incidentUpdated') {
        loadFeed();
        loadIncidents('inc-list', cFilter, 'all');
        updateStats();
      }
      if (e.key === 'statsUpdated') {
        updateStats();
      }
    });
  });

  function filterTable(status, btn) {
    cFilter = status;
    activateFilter(btn, '.filter-pill');
    loadIncidents('inc-list', status, 'all');
  }
</script>
</body>
</html>