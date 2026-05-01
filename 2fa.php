<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$user_name=$_SESSION['user_name'];
$back = $_SESSION['role']==='admin'?'admin.php':'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>2FA Setup — Cyber Incident System</title>
<link rel="stylesheet" href="css/style.css">
<style>
.tfa-page { min-height:100vh;display:flex; }
.tfa-left {
  width:420px;flex-shrink:0;
  background:radial-gradient(ellipse at 40% 50%,rgba(0,212,255,0.07) 0%,transparent 60%),
             radial-gradient(ellipse at 80% 20%,rgba(107,93,255,0.07) 0%,transparent 50%),var(--bg-dark);
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:60px 40px;position:relative;overflow:hidden;border-right:1px solid var(--border);
}
.tfa-left::before { content:'';position:absolute;inset:0;background-image:radial-gradient(circle at 2px 2px,rgba(0,212,255,0.05) 1px,transparent 0);background-size:32px 32px; }
.tfa-left::after { content:'';position:absolute;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(0,212,255,0.3),transparent);animation:scanline 8s linear infinite;z-index:0; }
.left-inner { position:relative;z-index:1;text-align:center; }
.tfa-right { flex:1;display:flex;align-items:center;justify-content:center;padding:40px; }
.tfa-form { width:100%;max-width:520px; }

/* Progress */
.step-progress { display:flex;align-items:center;margin-bottom:36px; }
.step-circle {
  width:36px;height:36px;border-radius:50%;
  border:2px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  font-size:13px;font-weight:800;font-family:var(--font-head);
  color:var(--text-muted);transition:all 0.4s;flex-shrink:0;
}
.step-circle.active { border-color:var(--neon-cyan);color:var(--neon-cyan);box-shadow:0 0 12px rgba(0,212,255,0.3); }
.step-circle.done { background:var(--neon-cyan);border-color:var(--neon-cyan);color:#000; }
.step-line { flex:1;height:2px;background:var(--border);position:relative;overflow:hidden; }
.step-line-inner { position:absolute;inset:0;background:linear-gradient(90deg,var(--neon-cyan),var(--neon-purple));transform:scaleX(0);transform-origin:left;transition:transform 0.6s ease; }
.step-line-inner.active { transform:scaleX(1); }
.step-label { font-size:10px;text-align:center;margin-top:6px;font-family:var(--font-head);font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted); }

/* Step panels */
.step-panel { display:none;animation:fadeIn 0.4s ease; }
.step-panel.active { display:block; }

.tfa-title { font-size:26px;font-family:var(--font-head);font-weight:800;margin-bottom:6px; }
.tfa-sub { font-size:14px;color:var(--text-muted);margin-bottom:28px;line-height:1.6; }

/* Method cards */
.method-grid { display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:22px; }
.method-card {
  background:var(--bg-input);border:2px solid var(--border);border-radius:var(--radius-lg);
  padding:20px;cursor:pointer;transition:all 0.25s;text-align:center;
}
.method-card:hover { border-color:var(--border-cyan); }
.method-card.selected { border-color:var(--neon-cyan);background:rgba(0,212,255,0.06);box-shadow:0 0 20px rgba(0,212,255,0.12); }
.method-card.selected-purple { border-color:var(--neon-purple);background:rgba(107,93,255,0.06); }
.method-icon { font-size:32px;margin-bottom:10px; }
.method-name { font-size:14px;font-weight:700;font-family:var(--font-head); }
.method-desc { font-size:11px;color:var(--text-muted);margin-top:4px; }
.method-badge { display:inline-block;padding:2px 8px;border-radius:100px;font-size:10px;font-weight:700;font-family:var(--font-head);margin-top:8px; }
.badge-rec { background:rgba(0,212,255,0.1);color:var(--neon-cyan);border:1px solid var(--border-cyan); }
.badge-alt { background:rgba(107,93,255,0.1);color:var(--neon-purple);border:1px solid var(--border-purple); }

/* QR Code */
.qr-wrap { text-align:center;margin-bottom:24px; }
.qr-box {
  width:160px;height:160px;margin:0 auto;
  background:white;border-radius:var(--radius-md);
  display:flex;align-items:center;justify-content:center;
  font-size:90px;
  animation:pulse-qr 3s ease-in-out infinite;
  box-shadow:0 0 30px rgba(0,212,255,0.3);
}
.qr-hint { font-size:12px;color:var(--text-muted);margin-top:10px;font-family:var(--font-head); }

/* OTP inputs */
.otp-wrap { display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:20px; }
.otp-input {
  width:44px;height:52px;text-align:center;
  background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-md);
  color:var(--text-primary);font-size:20px;font-weight:700;font-family:var(--font-head);
  outline:none;transition:border-color 0.2s;
}
.otp-input:focus { border-color:var(--neon-cyan);box-shadow:0 0 0 2px rgba(0,212,255,0.15); }
.otp-dash { font-size:20px;color:var(--text-muted); }

/* Backup codes */
.backup-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:20px; }
.backup-code {
  background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-md);
  padding:10px;display:flex;align-items:center;justify-content:space-between;
  font-family:'Share Tech Mono',monospace;font-size:12px;color:var(--neon-cyan);
  transition:border-color 0.2s;cursor:pointer;
}
.backup-code:hover { border-color:var(--border-cyan); }
.copy-icon { font-size:12px;color:var(--text-muted); }

.warning-box {
  background:rgba(255,159,67,0.08);border:1px solid rgba(255,159,67,0.3);
  border-left:3px solid var(--orange);border-radius:var(--radius-md);
  padding:12px 14px;font-size:12px;color:#fbbf24;line-height:1.7;margin-bottom:20px;
}
.warning-box strong { color:var(--orange); }

.btn-row { display:flex;gap:10px;margin-top:4px; }

/* Robot shield */
.robot-shield { position:relative;width:200px;height:220px;margin:0 auto; }
.shield-bot { font-size:100px;position:absolute;top:30px;left:50%;transform:translateX(-50%);filter:drop-shadow(0 0 20px rgba(0,212,255,0.5)); }
.shield-badge { position:absolute;bottom:20px;right:20px;background:linear-gradient(135deg,var(--neon-cyan),var(--neon-purple));border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:0 0 20px rgba(0,212,255,0.4);animation:glow-pulse 3s ease-in-out infinite; }

@media(max-width:900px){.tfa-left{display:none}.tfa-right{padding:24px}}
</style>
</head>
<body>
<div class="scanline-overlay"></div>

<nav class="navbar">
  <div class="nav-logo"><div class="logo-mark">🛡️</div>Cyber<span> Incident System</span></div>
  <div class="nav-links">
    <a href="<?=$back?>" class="nav-link">🏠 Dashboard</a>
    <a href="2fa.php" class="nav-link active">🔐 2FA Setup</a>
  </div>
  <div class="nav-right">
    <div class="user-avatar"><?= strtoupper(substr($user_name,0,1)) ?></div>
    <a href="logout.php" style="font-size:12px;color:var(--text-muted);padding:0 8px;">Sign Out</a>
  </div>
</nav>

<div class="tfa-page">

  <!-- LEFT -->
  <div class="tfa-left">
    <div class="left-inner">
      <div class="robot-shield" style="animation:float 4s ease-in-out infinite">
        <div class="shield-bot">🤖</div>
        <div class="shield-badge">🔒</div>
        <div class="ring" style="width:200px;height:200px;position:absolute;top:10px;left:10px;border-color:rgba(0,212,255,0.25);animation:rotate-ring 8s linear infinite;"></div>
        <div class="ring" style="width:240px;height:240px;position:absolute;top:-10px;left:-10px;border-color:rgba(107,93,255,0.15);border-style:dashed;animation:rotate-ring 12s linear infinite reverse;"></div>
      </div>
      <div style="margin-top:24px;">
        <h2 style="font-size:24px;font-family:var(--font-head);font-weight:800;">Fortify Your Access</h2>
        <p style="font-size:13px;color:var(--text-muted);margin-top:8px;line-height:1.7;max-width:280px;">Two-factor authentication adds an extra layer of security to protect your account from unauthorized access.</p>
        <div style="display:flex;gap:16px;margin-top:24px;">
          <div style="text-align:center;"><div style="font-size:20px;font-weight:800;font-family:var(--font-head);color:var(--neon-cyan);">99%</div><div style="font-size:10px;color:var(--text-muted);">Breach reduction</div></div>
          <div style="text-align:center;"><div style="font-size:20px;font-weight:800;font-family:var(--font-head);color:var(--neon-purple);">30s</div><div style="font-size:10px;color:var(--text-muted);">Setup time</div></div>
          <div style="text-align:center;"><div style="font-size:20px;font-weight:800;font-family:var(--font-head);color:var(--green);">Free</div><div style="font-size:10px;color:var(--text-muted);">Always</div></div>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT -->
  <div class="tfa-right">
    <div class="tfa-form">

      <!-- Progress indicator -->
      <div class="step-progress">
        <div style="text-align:center;">
          <div class="step-circle active" id="sc1">1</div>
          <div class="step-label" style="color:var(--neon-cyan);">Method</div>
        </div>
        <div class="step-line"><div class="step-line-inner" id="sl1"></div></div>
        <div style="text-align:center;">
          <div class="step-circle" id="sc2">2</div>
          <div class="step-label">QR Code</div>
        </div>
        <div class="step-line"><div class="step-line-inner" id="sl2"></div></div>
        <div style="text-align:center;">
          <div class="step-circle" id="sc3">3</div>
          <div class="step-label">Backup</div>
        </div>
      </div>

      <!-- STEP 1 -->
      <div class="step-panel active" id="step-1">
        <div class="tfa-title">Choose Security Method</div>
        <div class="tfa-sub">Select how you want to receive your authentication codes.</div>

        <div class="method-grid">
          <div class="method-card selected" id="m-app" onclick="selectMethod('app')">
            <div class="method-icon">📱</div>
            <div class="method-name">Auth App</div>
            <div class="method-desc">Google Authenticator, Authy, or similar</div>
            <div class="method-badge badge-rec">⭐ Recommended</div>
          </div>
          <div class="method-card" id="m-email" onclick="selectMethod('email')">
            <div class="method-icon">✉️</div>
            <div class="method-name">Email Code</div>
            <div class="method-desc">Receive codes via your registered email</div>
            <div class="method-badge badge-alt">Alternative</div>
          </div>
        </div>

        <button class="btn-gradient" onclick="goStep(2)"><span>Next: Scan QR Code &rarr;</span></button>
      </div>

      <!-- STEP 2 -->
      <div class="step-panel" id="step-2">
        <div class="tfa-title">Scan QR Code</div>
        <div class="tfa-sub">Open your authenticator app and scan the QR code below.</div>

        <div class="qr-wrap">
          <div class="qr-box">🔲</div>
          <div class="qr-hint">Or enter code manually: <span style="color:var(--neon-cyan);font-family:'Share Tech Mono',monospace;font-size:13px;">JBSW Y3DP EHPK 3PXP</span></div>
        </div>

        <div style="font-size:12px;font-family:var(--font-head);font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;text-align:center;margin-bottom:12px;">Enter the 6-digit code</div>

        <div class="otp-wrap">
          <input class="otp-input" type="text" maxlength="1" id="otp1" oninput="otpNext(this,'otp2')">
          <input class="otp-input" type="text" maxlength="1" id="otp2" oninput="otpNext(this,'otp3')">
          <input class="otp-input" type="text" maxlength="1" id="otp3" oninput="otpNext(this,'otp4')">
          <span class="otp-dash">—</span>
          <input class="otp-input" type="text" maxlength="1" id="otp4" oninput="otpNext(this,'otp5')">
          <input class="otp-input" type="text" maxlength="1" id="otp5" oninput="otpNext(this,'otp6')">
          <input class="otp-input" type="text" maxlength="1" id="otp6" oninput="otpNext(this,null)">
        </div>

        <div class="btn-row">
          <button class="btn-outline-cyan" onclick="goStep(1)" style="flex:1;">← Go Back</button>
          <button class="btn-gradient" onclick="goStep(3)" style="flex:2;"><span>Complete Setup &rarr;</span></button>
        </div>
      </div>

      <!-- STEP 3 -->
      <div class="step-panel" id="step-3">
        <div class="tfa-title">Save Backup Codes</div>
        <div class="tfa-sub">Store these codes safely. Each can be used once if you lose your device.</div>

        <div class="backup-grid">
          <div class="backup-code" onclick="copyCode(this,'A1B2-C3D4')">A1B2-C3D4 <span class="copy-icon">📋</span></div>
          <div class="backup-code" onclick="copyCode(this,'E5F6-G7H8')">E5F6-G7H8 <span class="copy-icon">📋</span></div>
          <div class="backup-code" onclick="copyCode(this,'I9J0-K1L2')">I9J0-K1L2 <span class="copy-icon">📋</span></div>
          <div class="backup-code" onclick="copyCode(this,'M3N4-O5P6')">M3N4-O5P6 <span class="copy-icon">📋</span></div>
          <div class="backup-code" onclick="copyCode(this,'Q7R8-S9T0')">Q7R8-S9T0 <span class="copy-icon">📋</span></div>
          <div class="backup-code" onclick="copyCode(this,'U1V2-W3X4')">U1V2-W3X4 <span class="copy-icon">📋</span></div>
        </div>

        <div class="warning-box">
          <strong>⚠ Important:</strong> These backup codes will only be shown once. If you lose access to your authenticator app and don't have these codes, you will be locked out of your account. Store them in a secure, offline location.
        </div>

        <div class="btn-row" style="margin-bottom:14px;">
          <button class="btn-outline-cyan" style="flex:1;" onclick="alert('PDF download would trigger here in production.')">⬇ Download PDF</button>
          <button class="btn-outline-purple" style="flex:1;" onclick="window.print()">🖨 Print Codes</button>
        </div>

        <a href="<?=$back?>" class="btn-gradient"><span>✅ Finish &amp; Enter Dashboard</span></a>
      </div>

    </div>
  </div>

</div>

<script src="js/main.js"></script>
<script>
let selectedMethod = 'app';

function selectMethod(m) {
  selectedMethod = m;
  document.getElementById('m-app').className   = 'method-card' + (m==='app'?' selected':'');
  document.getElementById('m-email').className = 'method-card' + (m==='email'?' selected-purple':'');
}

function goStep(n) {
  document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('step-'+n).classList.add('active');

  // Update progress circles
  for (let i=1;i<=3;i++) {
    const sc = document.getElementById('sc'+i);
    if (i<n)       { sc.className='step-circle done'; sc.textContent='✓'; }
    else if (i===n){ sc.className='step-circle active'; sc.textContent=i; }
    else           { sc.className='step-circle'; sc.textContent=i; }
  }
  // Step lines
  if (document.getElementById('sl1')) document.getElementById('sl1').classList.toggle('active',n>=2);
  if (document.getElementById('sl2')) document.getElementById('sl2').classList.toggle('active',n>=3);
}

function otpNext(el,nextId) {
  if (el.value.length===1 && nextId) {
    document.getElementById(nextId)?.focus();
  }
}

function copyCode(el, code) {
  navigator.clipboard?.writeText(code);
  const icon = el.querySelector('.copy-icon');
  if (icon) { icon.textContent='✅'; setTimeout(()=>icon.textContent='📋',1500); }
}
</script>
</body>
</html>