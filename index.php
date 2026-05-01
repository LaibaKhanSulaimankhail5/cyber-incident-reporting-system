<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
    exit;
}

$error = '';
$email_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $email_val = htmlspecialchars($email);

    if (!$email || !$pass) {
        $error = 'Please enter your email and password.';
    } else {
        try {
            $s = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $s->execute([$email]);
            $u = $s->fetch(PDO::FETCH_ASSOC);

            $validPassword = false;
            if ($u) {
                if (password_verify($pass, $u['password'])) {
                    $validPassword = true;
                } elseif (strlen($u['password']) === 32 && md5($pass) === $u['password']) {
                    $validPassword = true;
                    $newHash = password_hash($pass, PASSWORD_BCRYPT);
                    $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$newHash, $u['id']]);
                }
            }

            if ($validPassword && $u) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $u['id'];
                $_SESSION['user_name'] = $u['name'];
                $_SESSION['role']      = $u['role'];
                header("Location: " . ($u['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'A system error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cyber Incident System — Secure Login</title>
<meta name="description" content="CyberGuard Threat Intelligence Platform — Secure operator login portal.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;500;600;700&display=swap">
<style>
@import url('https://api.fontshare.com/v2/css?f[]=cabinet-grotesk@800,700,400&f[]=satoshi@700,500,400&display=swap');

:root {
  --neon-cyan:#00d4ff; --neon-purple:#6b5dff;
  --bg-dark:#070b1d; --bg-card:#0a0e27; --bg-input:#0d1231;
  --text-primary:#f0f4ff; --text-muted:#4a5580;
  --red:#ff4757; --green:#00ff88;
}
*{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;font-family:'Satoshi',sans-serif;background:var(--bg-dark);color:var(--text-primary);overflow:hidden;}

/* ── Animations ── */
@keyframes beamMove{0%{top:-2px;opacity:0}5%{opacity:1}95%{opacity:1}100%{top:100%;opacity:0}}
@keyframes float{0%,100%{transform:translateY(0) rotate(0deg)}25%{transform:translateY(-14px) rotate(1deg)}75%{transform:translateY(-9px) rotate(-1deg)}}
@keyframes rSpin{from{transform:translate(-50%,-50%) rotate(0deg)}to{transform:translate(-50%,-50%) rotate(360deg)}}
@keyframes shieldGlow{0%,100%{box-shadow:0 0 10px rgba(0,212,255,.2)}50%{box-shadow:0 0 30px rgba(0,212,255,.55),0 0 60px rgba(0,212,255,.2)}}
@keyframes slideIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
@keyframes formUp{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:translateY(0)}}
@keyframes gridPulse{0%{opacity:.5}100%{opacity:1}}
@keyframes scanline-move{from{background-position:0 0}to{background-position:0 100%}}
@keyframes antPulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.4);opacity:.6}}
@keyframes eyeBlink{0%,90%,100%{ry:6}95%{ry:1}}
@keyframes mouthLed{0%,100%{opacity:1}50%{opacity:.2}}
@keyframes eqBar{0%,100%{height:4px}50%{height:12px}}
@keyframes popIn{0%{transform:scale(.5);opacity:0}100%{transform:scale(1);opacity:1}}

/* ── Layout ── */
.login-page{display:flex;height:100vh;overflow:hidden;}

/* ── LEFT COLUMN ── */
.lp-left{
  flex:1;position:relative;overflow:hidden;
  background:var(--bg-dark);
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:28px;
}
.circuit-dots{
  position:absolute;inset:0;pointer-events:none;
  background-image:radial-gradient(circle at 2px 2px,rgba(0,212,255,.05) 1px,transparent 0);
  background-size:32px 32px;
  animation:gridPulse 4s ease-in-out infinite alternate;
}
.blob-cyan{position:absolute;width:500px;height:500px;top:-100px;left:-100px;
  background:radial-gradient(ellipse,rgba(0,212,255,.07) 0%,transparent 70%);pointer-events:none;}
.blob-purple{position:absolute;width:500px;height:500px;bottom:-100px;right:-100px;
  background:radial-gradient(ellipse,rgba(107,93,255,.07) 0%,transparent 70%);pointer-events:none;}
.scan-beam{
  position:absolute;left:0;right:0;height:2px;top:-2px;
  background:linear-gradient(90deg,transparent,rgba(0,212,255,.6),transparent);
  animation:beamMove 6s linear infinite;pointer-events:none;
}
.lp-brand{text-align:center;z-index:2;}
.lp-brand h1{font-family:'Orbitron',monospace;font-size:36px;font-weight:900;letter-spacing:.04em;}
.lp-brand h1 span{color:var(--neon-cyan);text-shadow:0 0 20px rgba(0,212,255,.6);}
.lp-brand p{font-family:'Rajdhani',sans-serif;font-size:14px;color:var(--text-muted);margin-top:6px;letter-spacing:.08em;}

/* Robot wrap */
.robot-scene{position:relative;width:320px;height:320px;z-index:2;}
.ring{position:absolute;border-radius:50%;top:50%;left:50%;}
.ring-1{width:200px;height:200px;margin:-100px 0 0 -100px;border:1.5px solid var(--neon-cyan);animation:rSpin 8s linear infinite;}
.ring-1::before{content:'';position:absolute;width:10px;height:10px;background:var(--neon-cyan);border-radius:50%;top:-5px;left:50%;margin-left:-5px;box-shadow:0 0 12px var(--neon-cyan);}
.ring-2{width:254px;height:254px;margin:-127px 0 0 -127px;border:1.5px dashed rgba(107,93,255,.6);animation:rSpin 13s linear infinite reverse;}
.ring-2::before{content:'';position:absolute;width:8px;height:8px;background:var(--neon-purple);border-radius:50%;bottom:-4px;left:50%;margin-left:-4px;box-shadow:0 0 10px var(--neon-purple);}
.ring-3{width:312px;height:312px;margin:-156px 0 0 -156px;border:1px solid rgba(0,212,255,.12);animation:rSpin 22s linear infinite;}
.robot-svg{
  position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
  width:160px;height:175px;
  filter:drop-shadow(0 0 22px rgba(0,212,255,.45));
  animation:float 4s ease-in-out infinite;
}

.lp-stats{display:flex;gap:20px;font-family:'Orbitron',monospace;font-size:10px;color:var(--text-muted);letter-spacing:.1em;z-index:2;}
.lp-stats span{color:var(--neon-cyan);}

/* ── RIGHT COLUMN ── */
.lp-right{
  width:460px;flex-shrink:0;
  background:var(--bg-card);
  display:flex;flex-direction:column;justify-content:center;
  padding:48px 44px;
  position:relative;overflow:hidden;
}
.lp-right::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;
  background:linear-gradient(90deg,transparent,var(--neon-purple),var(--neon-cyan),transparent);}
.lp-right::after{content:'';position:absolute;top:-100px;right:-100px;width:300px;height:300px;
  background:radial-gradient(ellipse,rgba(107,93,255,.08) 0%,transparent 65%);pointer-events:none;}
.rp-inner{position:relative;z-index:1;animation:formUp .5s ease;}

.shield-icon{
  width:68px;height:68px;margin:0 auto 18px;
  display:flex;align-items:center;justify-content:center;
  font-size:28px;
  background:linear-gradient(135deg,rgba(0,212,255,.1),rgba(107,93,255,.1));
  border:1.5px solid rgba(0,212,255,.3);border-radius:50%;
  animation:shieldGlow 3s ease-in-out infinite;
}
.rp-title{text-align:center;margin-bottom:28px;}
.rp-title h2{font-family:'Orbitron',monospace;font-size:20px;font-weight:700;}
.rp-title h2 span{color:var(--neon-cyan);}
.rp-title p{font-family:'Rajdhani',sans-serif;font-size:12px;color:var(--text-muted);margin-top:4px;letter-spacing:.1em;}

.error-box{
  display:none;background:rgba(255,71,87,.08);border:1px solid rgba(255,71,87,.3);
  border-left:3px solid var(--red);color:#fca5a5;padding:12px 14px;
  border-radius:10px;font-size:13px;margin-bottom:18px;animation:slideIn .3s ease;
}
.error-box.show{display:block;}

.form-group{margin-bottom:16px;}
.form-label{display:block;font-size:11px;font-family:'Cabinet Grotesk',sans-serif;font-weight:700;
  text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:6px;}
.input-wrap{position:relative;}
.input-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:15px;color:var(--text-muted);pointer-events:none;}
.form-input{
  width:100%;background:var(--bg-input);border:1px solid rgba(255,255,255,.07);
  border-radius:12px;padding:13px 14px 13px 42px;
  color:var(--text-primary);font-size:14px;font-family:'Satoshi',sans-serif;
  transition:border-color .25s,box-shadow .25s;outline:none;
}
.form-input:focus{border-color:var(--neon-cyan);box-shadow:0 0 0 3px rgba(0,212,255,.12);}
.toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);
  background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:15px;}

.form-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;}
.form-row label{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-muted);cursor:pointer;}
.form-row a{font-size:12px;color:var(--neon-cyan);text-decoration:none;}

.btn-login{
  width:100%;padding:14px;border:none;cursor:pointer;
  background:linear-gradient(135deg,var(--neon-cyan) 0%,var(--neon-purple) 100%);
  color:#fff;font-family:'Rajdhani',sans-serif;font-size:16px;font-weight:700;
  text-transform:uppercase;letter-spacing:.08em;border-radius:50px;
  box-shadow:0 4px 15px rgba(0,212,255,.3);transition:all .3s;
  position:relative;overflow:hidden;
}
.btn-login::before{content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,var(--neon-purple),var(--neon-cyan));
  opacity:0;transition:opacity .3s;}
.btn-login span{position:relative;z-index:1;}
.btn-login:hover{transform:translateY(-3px);box-shadow:0 8px 25px rgba(0,212,255,.45);}
.btn-login:hover::before{opacity:1;}

.rp-register{text-align:center;margin-top:16px;font-size:13px;color:var(--text-muted);}
.rp-register a{color:var(--neon-cyan);font-weight:600;text-decoration:none;}

.demo-divider{display:flex;align-items:center;gap:10px;margin:18px 0 14px;color:var(--text-muted);font-size:11px;}
.demo-divider::before,.demo-divider::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.07);}

.demo-box{
  background:rgba(0,212,255,.03);border:1px solid rgba(0,212,255,.1);
  border-radius:10px;padding:12px 14px;font-size:11px;font-family:'Rajdhani',sans-serif;
  color:var(--text-muted);line-height:2;
}
.demo-box strong{color:var(--neon-cyan);}

/* ── Scanline overlay ── */
.scanline-overlay{
  position:fixed;inset:0;pointer-events:none;z-index:999;
  background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,212,255,.012) 3px,rgba(0,212,255,.012) 4px);
  animation:scanline-move 8s linear infinite;
}

/* ── Responsive ── */
@media(max-width:900px){
  .lp-left{display:none;}
  .lp-right{width:100%;}
  html,body{overflow:auto;}
}
</style>
</head>
<body>
<div class="scanline-overlay"></div>
<div class="login-page">

  <!-- ─────────── LEFT COLUMN ─────────── -->
  <div class="lp-left">
    <div class="circuit-dots"></div>
    <div class="blob-cyan"></div>
    <div class="blob-purple"></div>
    <div class="scan-beam"></div>

    <div class="lp-brand">
      <h1>Cyber<span> Incident System</span></h1>
      <p>// Threat Intelligence Platform</p>
    </div>

    <!-- Animated Robot -->
    <div class="robot-scene">
      <div class="ring ring-1"></div>
      <div class="ring ring-2"></div>
      <div class="ring ring-3"></div>
      <svg class="robot-svg" viewBox="0 0 160 175" fill="none" xmlns="http://www.w3.org/2000/svg">
        <!-- Antenna -->
        <line x1="80" y1="10" x2="80" y2="28" stroke="#00d4ff" stroke-width="2"/>
        <circle cx="80" cy="7" r="5" fill="#00d4ff">
          <animate attributeName="opacity" values="1;0.2;1" dur="1.5s" repeatCount="indefinite"/>
        </circle>
        <!-- Head -->
        <rect x="32" y="28" width="96" height="68" rx="14" fill="#0d1231" stroke="#00d4ff" stroke-width="1.5"/>
        <!-- Left Eye (cyan) -->
        <ellipse cx="57" cy="57" rx="12" ry="11" fill="#00d4ff" opacity=".9">
          <animate attributeName="ry" values="11;11;11;11;1;11;11" dur="3.5s" repeatCount="indefinite"/>
        </ellipse>
        <ellipse cx="57" cy="57" rx="6" ry="5.5" fill="#0d1231"/>
        <ellipse cx="55" cy="55" rx="2" ry="2" fill="#fff" opacity=".6"/>
        <!-- Right Eye (purple) -->
        <ellipse cx="103" cy="57" rx="12" ry="11" fill="#6b5dff" opacity=".9">
          <animate attributeName="ry" values="11;11;11;11;1;11;11" dur="3.5s" begin="0.2s" repeatCount="indefinite"/>
        </ellipse>
        <ellipse cx="103" cy="57" rx="6" ry="5.5" fill="#0d1231"/>
        <ellipse cx="101" cy="55" rx="2" ry="2" fill="#fff" opacity=".6"/>
        <!-- Mouth LEDs -->
        <rect x="60" y="80" width="10" height="8" rx="2" fill="#00d4ff">
          <animate attributeName="opacity" values="1;0.2;1" dur="1s" repeatCount="indefinite"/>
        </rect>
        <rect x="75" y="80" width="10" height="8" rx="2" fill="#6b5dff">
          <animate attributeName="opacity" values="1;0.2;1" dur="1s" begin=".33s" repeatCount="indefinite"/>
        </rect>
        <rect x="90" y="80" width="10" height="8" rx="2" fill="#00d4ff">
          <animate attributeName="opacity" values="1;0.2;1" dur="1s" begin=".66s" repeatCount="indefinite"/>
        </rect>
        <!-- Neck -->
        <rect x="68" y="96" width="24" height="10" rx="4" fill="#0d1231" stroke="#6b5dff" stroke-width="1"/>
        <!-- Body -->
        <rect x="20" y="106" width="120" height="60" rx="16" fill="#0d1231" stroke="#6b5dff" stroke-width="1.5"/>
        <!-- Chest panel -->
        <rect x="42" y="118" width="76" height="36" rx="8" fill="#070b1d" stroke="#00d4ff" stroke-width="1"/>
        <!-- EQ Bars -->
        <rect x="52" y="134" width="6" height="8" rx="1" fill="#00d4ff">
          <animate attributeName="height" values="8;16;4;12;8" dur="0.8s" repeatCount="indefinite"/>
          <animate attributeName="y" values="134;126;138;130;134" dur="0.8s" repeatCount="indefinite"/>
        </rect>
        <rect x="62" y="130" width="6" height="12" rx="1" fill="#6b5dff">
          <animate attributeName="height" values="12;4;16;6;12" dur="0.8s" begin=".15s" repeatCount="indefinite"/>
          <animate attributeName="y" values="130;138;126;136;130" dur="0.8s" begin=".15s" repeatCount="indefinite"/>
        </rect>
        <rect x="72" y="136" width="6" height="6" rx="1" fill="#00ff88">
          <animate attributeName="height" values="6;14;4;10;6" dur="0.8s" begin=".3s" repeatCount="indefinite"/>
          <animate attributeName="y" values="136;128;138;132;136" dur="0.8s" begin=".3s" repeatCount="indefinite"/>
        </rect>
        <rect x="82" y="128" width="6" height="14" rx="1" fill="#00d4ff">
          <animate attributeName="height" values="14;6;18;8;14" dur="0.8s" begin=".45s" repeatCount="indefinite"/>
          <animate attributeName="y" values="128;136;124;134;128" dur="0.8s" begin=".45s" repeatCount="indefinite"/>
        </rect>
        <rect x="92" y="134" width="6" height="8" rx="1" fill="#6b5dff">
          <animate attributeName="height" values="8;18;4;12;8" dur="0.8s" begin=".6s" repeatCount="indefinite"/>
          <animate attributeName="y" values="134;124;138;130;134" dur="0.8s" begin=".6s" repeatCount="indefinite"/>
        </rect>
        <!-- Left Arm -->
        <rect x="0" y="114" width="20" height="38" rx="10" fill="#0d1231" stroke="#00d4ff" stroke-width="1"/>
        <circle cx="10" cy="114" r="5" fill="#0d1231" stroke="#00d4ff" stroke-width="1"/>
        <!-- Right Arm -->
        <rect x="140" y="114" width="20" height="38" rx="10" fill="#0d1231" stroke="#00d4ff" stroke-width="1"/>
        <circle cx="150" cy="114" r="5" fill="#0d1231" stroke="#00d4ff" stroke-width="1"/>
      </svg>
    </div>

    <div class="lp-stats">
      <div><span>99.9%</span> Uptime</div>
      <div>·</div>
      <div><span>256+</span> Blocked</div>
      <div>·</div>
      <div><span>24/7</span> Monitor</div>
    </div>
  </div>

  <!-- ─────────── RIGHT COLUMN ─────────── -->
  <div class="lp-right">
    <div class="rp-inner">
      <div class="shield-icon">🛡️</div>
      <div class="rp-title">
        <h2>Cyber<span> Incident System</span></h2>
        <p>// SECURE ACCESS PORTAL</p>
      </div>

      <?php if ($error): ?>
      <div class="error-box show">⚠ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="" novalidate>
        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <div class="input-wrap">
            <span class="input-icon">✉</span>
            <input class="form-input" type="email" id="email" name="email"
                   placeholder="operator@company.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   required autofocus>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔑</span>
            <input class="form-input" type="password" id="password" name="password"
                   placeholder="••••••••••" required style="padding-right:44px;">
            <button type="button" class="toggle-pw" onclick="togglePw('password',this)">👁</button>
          </div>
        </div>

        <div class="form-row">
          <label>
            <input type="checkbox" name="remember"> Remember me
          </label>
          <a href="#">Forgot password?</a>
        </div>

        <button type="submit" class="btn-login"><span>ACCESS SYSTEM →</span></button>
      </form>

      <div class="rp-register">
        New operator? <a href="register.php">Create Account →</a>
      </div>

      <div class="demo-divider">Demo Credentials</div>
      <div class="demo-box">
        👑 <strong>Admin:</strong> admin@company.com / admin123<br>
        👤 <strong>User:</strong> john@company.com / pass123
      </div>
    </div>
  </div>

</div>

<script>
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  if (!inp) return;
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}
</script>
</body>
</html>