<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$user_id = (int) $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$role = $_SESSION['role'];
require_once __DIR__.'/config/db.php';
$inc_id = intval($_GET['id'] ?? 0);

if ($inc_id === 0) {
    // Fetch latest for this user (or overall if admin)
    if ($role === 'admin') {
        $st = $pdo->query("SELECT id FROM incidents ORDER BY id DESC LIMIT 1");
    } else {
        $st = $pdo->prepare("SELECT id FROM incidents WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $st->execute([$user_id]);
    }
    $inc_id = (int)$st->fetchColumn();
}

if (!$inc_id) {
    header('Location: ' . ($role === 'admin' ? 'admin.php' : 'dashboard.php'));
    exit;
}

$s = $pdo->prepare("SELECT i.*, u.name reporter FROM incidents i JOIN users u ON i.user_id=u.id WHERE i.id = ?");
$s->execute([$inc_id]);
$inc = $s->fetch();

if (!$inc || ($role !== 'admin' && (int)$inc['user_id'] !== $user_id)) {
    header('Location: ' . ($role === 'admin' ? 'admin.php' : 'dashboard.php'));
    exit;
}
$back = $role === 'admin' ? 'admin.php' : 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Incident Detail — Cyber Incident System</title>

<link rel="stylesheet" href="css/style.css">
<style>
.inc-layout { max-width:1100px;margin:0 auto;padding:24px; }
.breadcrumb { display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text-muted);margin-bottom:20px;font-family:var(--font-head);text-transform:uppercase;letter-spacing:0.06em; }
.breadcrumb a { color:var(--neon-cyan);text-decoration:none; }
.breadcrumb span { color:var(--text-muted); }
.inc-hero { background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;margin-bottom:20px;position:relative;overflow:hidden; }
.inc-hero::before { content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--neon-cyan),var(--neon-purple),transparent); }
.inc-title-row { display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap;margin-bottom:14px; }
.inc-num { font-family:var(--font-head);font-size:13px;color:var(--text-muted); }
.inc-hero h1 { font-size:20px;font-family:var(--font-head);font-weight:800;flex:1; }
.progress-section { margin-top:16px; }
.prog-label { display:flex;align-items:center;justify-content:space-between;font-size:12px;font-family:var(--font-head);font-weight:700;color:var(--text-secondary);margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em; }
.prog-wrap { background:rgba(255,255,255,0.05);border-radius:100px;overflow:hidden;height:8px;margin-bottom:10px; }
.prog-fill { height:100%;border-radius:100px;background:linear-gradient(90deg,var(--neon-cyan),var(--neon-purple));box-shadow:0 0 10px rgba(0,212,255,0.4);transition:width 1.5s ease;width:0; }
.milestones { display:flex;justify-content:space-between;position:relative; }
.milestone { text-align:center;flex:1;position:relative; }
.milestone::before { content:'';position:absolute;top:6px;left:-50%;right:50%;height:1px;background:var(--border); }
.milestone:first-child::before { display:none; }
.ms-dot { width:14px;height:14px;border-radius:50%;margin:0 auto 6px;border:2px solid var(--border); }
.ms-dot.done { border-color:var(--neon-cyan);background:var(--neon-cyan);box-shadow:0 0 8px var(--neon-cyan); }
.ms-dot.active { border-color:var(--neon-purple);background:transparent;box-shadow:0 0 8px var(--neon-purple);animation:pulse-glow 1.5s infinite; }
.ms-label { font-size:10px;color:var(--text-muted);font-family:var(--font-head);font-weight:700;text-transform:uppercase;letter-spacing:0.05em; }
.two-col { display:grid;grid-template-columns:1fr 360px;gap:20px; }
.section-card { background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:16px; }
.section-head { padding:14px 18px;border-bottom:1px solid var(--border);background:rgba(0,0,0,0.2); }
.section-head h3 { font-size:14px;font-family:var(--font-head);font-weight:700; }
.section-body { padding:18px; }
.evidence-card { background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px;display:flex;align-items:center;gap:12px;margin-bottom:10px;transition:all 0.2s;cursor:pointer; }
.evidence-card:hover { border-color:var(--border-cyan);background:var(--bg-hover); }
.ev-icon { font-size:24px; }
.ev-name { font-size:13px;font-weight:600;font-family:var(--font-head); }
.ev-meta { font-size:11px;color:var(--text-muted);margin-top:2px; }
.ev-badge { margin-left:auto;font-size:11px;padding:3px 8px;border-radius:100px;font-family:var(--font-head);font-weight:700; }
.ev-badge.pcap { background:rgba(0,212,255,0.1);color:var(--neon-cyan);border:1px solid var(--border-cyan); }
.ev-badge.img  { background:rgba(107,93,255,0.1);color:var(--neon-purple);border:1px solid var(--border-purple); }
.ai-step { background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px;margin-bottom:10px;transition:all 0.2s; }
.ai-step:hover { border-color:var(--border-cyan); }
.step-row { display:flex;align-items:flex-start;gap:12px; }
.step-icon { width:36px;height:36px;border-radius:10px;background:rgba(0,212,255,0.1);border:1px solid var(--border-cyan);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0; }
.step-title { font-size:13px;font-weight:700;font-family:var(--font-head);color:var(--text-primary); }
.step-desc { font-size:12px;color:var(--text-muted);margin-top:3px;line-height:1.6; }
.note-item { display:flex;gap:10px;margin-bottom:14px; }
.note-avatar { width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--neon-cyan),var(--neon-purple));display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0; }
.note-bubble { background:var(--bg-surface);border:1px solid var(--border);border-radius:0 var(--radius-md) var(--radius-md) var(--radius-md);padding:10px 12px;flex:1; }
.note-author { font-size:11px;font-weight:700;color:var(--neon-cyan);font-family:var(--font-head); }
.note-text { font-size:12px;color:var(--text-secondary);margin-top:3px;line-height:1.5; }
.note-time { font-size:10px;color:var(--text-muted);margin-top:4px; }
.note-input-row { display:flex;gap:8px;margin-top:14px; }
.note-input { flex:1;background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-md);padding:10px 14px;color:var(--text-primary);font-size:13px;font-family:var(--font-body);outline:none; }
.note-input:focus { border-color:var(--neon-cyan); }
.send-btn { padding:10px 16px;background:linear-gradient(135deg,var(--neon-cyan),var(--neon-purple));border:none;border-radius:var(--radius-md);color:#fff;font-weight:700;cursor:pointer;font-size:13px;transition:all 0.2s; }
.send-btn:hover { transform:translateY(-1px);box-shadow:0 4px 15px rgba(0,212,255,0.3); }
.action-bar { display:flex;gap:10px;margin-top:20px;flex-wrap:wrap; }

@media(max-width:960px){.two-col{grid-template-columns:1fr}}
</style>
</head>
<body>
<nav class="navbar">
  <div class="nav-logo"><div class="logo-mark">🛡️</div>Cyber<span> Incident System</span></div>
  <div class="nav-links">
    <a href="<?=htmlspecialchars($back, ENT_QUOTES, 'UTF-8')?>" class="nav-link">🏠 Dashboard</a>
    <a href="report.php" class="nav-link">⚠️ Report</a>
    <a href="incident.php" class="nav-link active">🔍 Investigate</a>
  </div>
  <div class="nav-right">
    <div class="user-avatar"><?= strtoupper(substr($user_name,0,1)) ?></div>
    <a href="logout.php" style="font-size:12px;color:var(--text-muted);padding:0 8px;">Sign Out</a>
  </div>
</nav>

<div class="inc-layout">

  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <a href="<?=htmlspecialchars($back, ENT_QUOTES, 'UTF-8')?>">Dashboard</a>
    <span>›</span>
    <span>Investigations</span>
    <span>›</span>
    <span style="color:var(--neon-cyan);">#INC-<?=str_pad($inc['id'],3,'0',STR_PAD_LEFT)?>-DELTA</span>
  </div>

  <!-- Hero -->
  <div class="inc-hero">
    <div class="inc-title-row">
      <div>
        <div class="inc-num">#INC-<?=str_pad($inc['id'],3,'0',STR_PAD_LEFT)?>-DELTA &nbsp;·&nbsp; <?=date('d M Y, H:i',strtotime($inc['reported_at']))?></div>
        <h1><?=htmlspecialchars($inc['title'])?></h1>
      </div>
      <div style="display:flex;gap:8px;flex-shrink:0;flex-wrap:wrap;">
        <span class="badge badge-priority">PRIORITY</span>
        <span class="badge badge-<?=htmlspecialchars($inc['severity'], ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars(strtoupper($inc['severity']), ENT_QUOTES, 'UTF-8')?></span>
        <span class="badge badge-<?=htmlspecialchars($inc['status'], ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars(ucfirst($inc['status']), ENT_QUOTES, 'UTF-8')?></span>
      </div>
    </div>

    <!-- Progress -->
    <div class="progress-section">
      <div class="prog-label">
        <span>Investigation Progress</span>
        <span style="color:var(--neon-cyan);">68%</span>
      </div>
      <div class="prog-wrap"><div class="prog-fill" id="prog-bar"></div></div>
      <div class="milestones">
        <div class="milestone"><div class="ms-dot done"></div><div class="ms-label">Reported</div></div>
        <div class="milestone"><div class="ms-dot done"></div><div class="ms-label">Triaged</div></div>
        <div class="milestone"><div class="ms-dot active"></div><div class="ms-label">Analyzing</div></div>
        <div class="milestone"><div class="ms-dot"></div><div class="ms-label">Resolved</div></div>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="section-card">
    <div class="tabs">
      <button class="tab-btn active" onclick="switchTab('tab-overview',this)">Overview</button>
      <button class="tab-btn" onclick="switchTab('tab-timeline',this)">Timeline</button>
      <button class="tab-btn" onclick="switchTab('tab-evidence',this)">Evidence</button>
      <button class="tab-btn" onclick="switchTab('tab-mitigation',this)">Mitigation</button>
    </div>
    <div style="padding:20px;">

      <!-- Overview Tab -->
      <div id="tab-overview" class="tab-panel active">
        <div style="font-size:13px;color:var(--text-secondary);line-height:1.8;margin-bottom:14px;"><?=htmlspecialchars($inc['description'], ENT_QUOTES, 'UTF-8')?></div>
        <?php if($inc['ai_analysis']): ?>
        <div class="ai-box show"><div class="ai-label">AI Threat Analysis</div><div class="ai-content"><?=htmlspecialchars($inc['ai_analysis'], ENT_QUOTES, 'UTF-8')?></div></div>
        <?php endif; ?>
        <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:14px;font-size:12px;font-family:var(--font-head);text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);">
          <span>📁 Type: <strong style="color:var(--text-primary);"><?=htmlspecialchars(ucwords(str_replace('_',' ',$inc['incident_type'])), ENT_QUOTES, 'UTF-8')?></strong></span>
          <span>👤 Reporter: <strong style="color:var(--text-primary);"><?=htmlspecialchars($inc['reporter']??'Unknown', ENT_QUOTES, 'UTF-8')?></strong></span>
          <span>🕐 Reported: <strong style="color:var(--text-primary);"><?=htmlspecialchars(date('d M Y H:i',strtotime($inc['reported_at'])), ENT_QUOTES, 'UTF-8')?></strong></span>
        </div>
      </div>

      <!-- Timeline Tab -->
      <div id="tab-timeline" class="tab-panel">
        <div class="timeline">
          <div class="tl-item"><div class="tl-dot cyan"></div><strong style="font-size:13px;font-family:var(--font-head);">Incident Reported</strong><div style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?=htmlspecialchars(date('d M Y H:i',strtotime($inc['reported_at'])), ENT_QUOTES, 'UTF-8')?></div><div style="font-size:12px;color:var(--text-secondary);margin-top:4px;">Incident logged by <?=htmlspecialchars($inc['reporter']??'User', ENT_QUOTES, 'UTF-8')?>. Automated AI analysis triggered.</div></div>
          <div class="tl-item"><div class="tl-dot orange"></div><strong style="font-size:13px;font-family:var(--font-head);">Alert Escalated</strong><div style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?=htmlspecialchars(date('d M Y H:i',strtotime($inc['reported_at'])+900), ENT_QUOTES, 'UTF-8')?></div><div style="font-size:12px;color:var(--text-secondary);margin-top:4px;">Security team notified. Priority classification assigned based on severity level.</div></div>
          <div class="tl-item"><div class="tl-dot purple"></div><strong style="font-size:13px;font-family:var(--font-head);">Investigation Started</strong><div style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?=htmlspecialchars(date('d M Y H:i',strtotime($inc['reported_at'])+2700), ENT_QUOTES, 'UTF-8')?></div><div style="font-size:12px;color:var(--text-secondary);margin-top:4px;">Security analyst assigned. Evidence collection in progress. Forensic imaging initiated.</div></div>
        </div>
      </div>

      <!-- Evidence Tab -->
      <div id="tab-evidence" class="tab-panel">
        <div class="evidence-card"><span class="ev-icon">📦</span><div><div class="ev-name">network_logs.pcap</div><div class="ev-meta">Packet capture · 14.2 MB · Uploaded <?=date('d M Y')?></div></div><span class="ev-badge pcap">PCAP</span></div>
        <div class="evidence-card"><span class="ev-icon">🗺️</span><div><div class="ev-name">threat_map_vis.png</div><div class="ev-meta">Threat map visualization · 3.8 MB · Uploaded <?=date('d M Y')?></div></div><span class="ev-badge img">IMG</span></div>
        <button class="btn-outline-cyan" style="margin-top:8px;width:auto;">+ Upload Evidence</button>
      </div>

      <!-- Mitigation Tab -->
      <div id="tab-mitigation" class="tab-panel">
        <div class="ai-step"><div class="step-row"><div class="step-icon">🔒</div><div><div class="step-title">Isolate Affected Systems</div><div class="step-desc">Immediately disconnect affected endpoints from the network to prevent lateral movement and further compromise.</div></div></div></div>
        <div class="ai-step"><div class="step-row"><div class="step-icon">🔑</div><div><div class="step-title">Reset Credentials</div><div class="step-desc">Force password reset on all potentially compromised accounts. Enable MFA where not yet active.</div></div></div></div>
        <div class="ai-step"><div class="step-row"><div class="step-icon">📋</div><div><div class="step-title">Forensic Analysis</div><div class="step-desc">Preserve system images before remediation. Analyze logs for IOCs and establish full attack timeline.</div></div></div></div>
      </div>

    </div>
  </div>

  <!-- Two column -->
  <div class="two-col">

    <!-- Left: Notes -->
    <div class="section-card">
      <div class="section-head"><h3>💬 Team Collaboration Notes</h3></div>
      <div class="section-body">
        <div class="note-item">
          <div class="note-avatar">SA</div>
          <div class="note-bubble"><div class="note-author">Security Analyst</div><div class="note-text">Initial triage complete. Network logs show RDP brute force pattern. Isolating workstations WS-14 and WS-17.</div><div class="note-time"><?=date('H:i',strtotime('-45 min'))?></div></div>
        </div>
        <div class="note-item">
          <div class="note-avatar" style="background:linear-gradient(135deg,var(--neon-purple),var(--neon-pink))">IT</div>
          <div class="note-bubble"><div class="note-author" style="color:var(--neon-purple);">IT Admin</div><div class="note-text">Accounts locked. Forensic image captured from WS-14. Running Volatility on memory dump now.</div><div class="note-time"><?=date('H:i',strtotime('-20 min'))?></div></div>
        </div>
        <div class="note-input-row">
          <input class="note-input" type="text" id="note-input" placeholder="Add investigation note...">
          <button class="send-btn" onclick="addNote()">Send</button>
        </div>
        <div id="notes-area"></div>
      </div>
    </div>

    <!-- Right: Actions -->
    <div>
      <div class="section-card">
        <div class="section-head"><h3>⚡ Quick Actions</h3></div>
        <div class="section-body">
          <div class="action-bar">
            <button class="btn-outline-cyan" style="width:100%;justify-content:center;margin-bottom:8px;" onclick="runDiagnostics(<?=$inc['id']?>)">🤖 Run AI Diagnostics</button>
            <button class="btn-outline-cyan" style="width:100%;justify-content:center;margin-bottom:8px;">👤 Assign to Self</button>
            <button class="btn-gradient" style="width:100%;"><span>✅ Mark Resolved</span></button>
          </div>
          <div style="margin-top:14px;">
            <select class="form-input" onchange="updateStatus(<?=$inc['id']?>,this.value, 'inc-list','all','all')">
              <option value="open" <?=$inc['status']==='open'?'selected':''?>>🔴 Open</option>
              <option value="investigating" <?=$inc['status']==='investigating'?'selected':''?>>🔵 Investigating</option>
              <option value="resolved" <?=$inc['status']==='resolved'?'selected':''?>>🟢 Resolved</option>
              <option value="closed" <?=$inc['status']==='closed'?'selected':''?>>⚫ Closed</option>
            </select>
          </div>
        </div>
      </div>
    </div>

  </div>

</div>


<script src="js/main.js"></script>
<script>window.CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>';</script>
<script>
  window.CURRENT_USER = <?= (int)$_SESSION['user_id'] ?>;
  setTimeout(() => {
    const bar = document.getElementById('prog-bar');
    if (bar) bar.style.width = '68%';
  }, 300);

  function addNote() {
    const inp = document.getElementById('note-input');
    const val = inp.value.trim();
    if (!val) return;
    const area = document.getElementById('notes-area');
    const now = new Date().toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'});
    const name = '<?= strtoupper(substr($user_name,0,2)) ?>';
    area.innerHTML += `<div class="note-item" style="margin-top:10px;animation:fadeIn 0.3s ease"><div class="note-avatar" style="background:linear-gradient(135deg,var(--green),var(--neon-cyan))">${name}</div><div class="note-bubble"><div class="note-author" style="color:var(--green);"><?= htmlspecialchars($user_name) ?></div><div class="note-text">${escapeHtml(val)}</div><div class="note-time">${now}</div></div></div>`;
    inp.value='';
  }

  function runDiagnostics(id) {
    const btn = event.currentTarget;
    const oldText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '🤖 Analyzing...';
    
    ajaxPost('ajax/ai_analyze.php', { incident_id: id })
      .then(d => {
        if(d.status === 'success') {
          location.reload(); // Refresh to show new analysis
        } else {
          alert('AI Error: ' + d.message);
          btn.disabled = false;
          btn.innerHTML = oldText;
        }
      })
      .catch(e => {
        alert('Network error');
        btn.disabled = false;
        btn.innerHTML = oldText;
      });
  }
</script>
</body>
</html>