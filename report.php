<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$user_id=$_SESSION['user_id']; $user_name=$_SESSION['user_name']; $role=$_SESSION['role'];
$back = $role==='admin'?'admin.php':'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Report Incident — Cyber Incident System</title>
<link rel="stylesheet" href="css/style.css">
<style>
.report-page { max-width:800px;margin:0 auto;padding:32px 24px; }
.form-card { background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-xl);overflow:hidden;position:relative; }
.form-card::before { content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--neon-cyan),var(--neon-purple),transparent); }
.form-header { padding:22px 28px;border-bottom:1px solid var(--border);background:rgba(0,0,0,0.2);display:flex;align-items:center;justify-content:space-between; }
.form-header h1 { font-size:18px;font-family:var(--font-head);font-weight:800; }
.ai-pill { display:flex;align-items:center;gap:6px;font-size:11px;font-family:var(--font-head);font-weight:700;color:var(--neon-cyan);background:rgba(0,212,255,0.08);border:1px solid var(--border-cyan);border-radius:100px;padding:5px 12px; }
.ai-pill::before { content:'';width:6px;height:6px;background:var(--neon-cyan);border-radius:50%;animation:pulse-glow 1.5s infinite; }
.form-body { padding:28px; }
.protocol-box { background:rgba(0,212,255,0.03);border:1px solid var(--border-cyan);border-radius:var(--radius-md);padding:14px 16px;margin-top:16px;font-size:12px;color:var(--text-muted);line-height:1.9; }
.protocol-box strong { color:var(--neon-cyan); }
</style>
</head>
<body>
  
<div class="scanline-overlay"></div>
<nav class="navbar">
  <div class="nav-logo"><div class="logo-mark">🛡️</div>Cyber<span> Incident System</span></div>
  <div class="nav-links">
    <a href="<?=htmlspecialchars($back, ENT_QUOTES, 'UTF-8')?>" class="nav-link">🏠 Dashboard</a>

    <a href="report.php" class="nav-link active">⚠️ Report</a>
    <a href="incident.php" class="nav-link">🔍 Investigate</a>
    <a href="2fa.php" class="nav-link">🔐 2FA</a>
  </div>
  <div class="nav-right">
    <div class="user-avatar" style="width:32px;height:32px;font-size:13px;"><?=htmlspecialchars(strtoupper(substr($user_name,0,1)), ENT_QUOTES, 'UTF-8')?></div>
    <a href="logout.php" style="font-size:12px;color:var(--text-muted);padding:0 8px;">Sign Out</a>
  </div>
</nav>

<div class="report-page">
  <div style="margin-bottom:20px;">
    <h1 style="font-size:22px;font-family:var(--font-head);">Report Cyber Incident</h1>
    <div style="font-size:13px;color:var(--text-muted);margin-top:4px;">All submissions are encrypted, logged, and analyzed by our AI engine instantly.</div>
  </div>

<?php if ($role === 'admin'): ?>
  <div class="form-card">
    <div class="form-header" style="flex-wrap:wrap;gap:10px;">
      <h1>All Incident Reports</h1>
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <button class="filter-pill active" onclick="filterInc('all','all',this)">All</button>
        <button class="filter-pill" onclick="filterInc('open','all',this)">Open</button>
        <button class="filter-pill" onclick="filterInc('investigating','all',this)">Investigating</button>
        <button class="filter-pill" onclick="filterInc('resolved','all',this)">Resolved</button>
        <button class="filter-pill" onclick="filterInc('all','phishing',this)">Phishing</button>
        <button class="filter-pill" onclick="filterInc('all','malware',this)">Malware</button>
      </div>
    </div>
    <div class="form-body">
      <div id="inc-list"></div>
    </div>
  </div>
  
<?php else: ?>
  <div class="form-card">
    <div class="form-header">
      <h1>⚠️ Incident Details</h1>
      <div style="display:flex;gap:10px;align-items:center;">
        <div class="ai-pill">AI Analysis Active</div>
        <a href="<?=htmlspecialchars($back, ENT_QUOTES, 'UTF-8')?>" class="btn-outline-cyan" style="padding:4px 12px;font-size:11px;text-decoration:none;">&larr; Back</a>
      </div>
    </div>
    <div class="form-body">

      <div class="form-group">
        <label class="form-label">Incident Title *</label>
        <input class="form-input" type="text" id="title" placeholder="e.g. Suspicious login attempt on my account">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
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
        <textarea class="form-input" id="description" style="min-height:120px;" placeholder="Describe exactly what happened, when it occurred, what you did, and any suspicious details..."></textarea>
      </div>

      <button id="submit-btn" class="btn-gradient" onclick="submitIncident()">
        <span>Submit Incident Report &rarr;</span>
      </button>

      <div id="form-msg" class="alert-msg"></div>

      <div id="ai-result-box" class="ai-box" style="margin-top:16px;">
        <div class="ai-label">AI Threat Analysis</div>
        <div class="ai-content" id="ai-result-text"></div>
      </div>

      <div class="protocol-box">
        <strong>ℹ PROTOCOL NOTES</strong><br>
        → Do NOT click suspicious links before reporting · Screenshot evidence before acting<br>
        → Critical incidents are automatically escalated to admin · AI analysis is instant
      </div>

    </div>
  </div>
<?php endif; ?>
</div>

<script src="js/main.js"></script>
<script>window.CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>';</script>
<script>
  window.CURRENT_USER = <?= (int)$user_id ?>;
<?php if ($role === 'admin'): ?>
  let rS = 'all', rT = 'all';
  function filterInc(status, type, btn) {
    rS = status; rT = type;
    activateFilter(btn, '.filter-pill');
    loadIncidents('inc-list', rS, rT);
  }
  document.addEventListener('DOMContentLoaded', function(){
    loadIncidents('inc-list', 'all', 'all');
  });
<?php else: ?>
  document.addEventListener('DOMContentLoaded', function(){
    setSeverity('medium');
  });
<?php endif; ?>
</script>
</body>
</html>