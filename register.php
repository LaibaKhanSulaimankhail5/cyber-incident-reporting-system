<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
    exit;
}

$error = ''; $success = false; $new_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/config/db.php';
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$pass || !$confirm) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $c = $pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
            $c->execute([$email]);
            if ($c->fetch()) {
                $error = 'Email already registered.';
            } else {
                $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'employee')")
                    ->execute([$name, $email, password_hash($pass, PASSWORD_BCRYPT)]);
                $success = true; $new_name = $name;
            }
        } catch (PDOException $e) {
            error_log('Register error: ' . $e->getMessage());
            $error = 'Database error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — Cyber Incident System</title>
<meta name="description" content="Create your CyberGuard operator account.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;500;600;700&display=swap">
<style>
@import url('https://api.fontshare.com/v2/css?f[]=cabinet-grotesk@800,700,400&f[]=satoshi@700,500,400&display=swap');

:root {
  --neon-cyan:#00d4ff; --neon-purple:#6b5dff;
  --bg-dark:#070b1d; --bg-card:#0a0e27; --bg-input:#0d1231;
  --text-primary:#f0f4ff; --text-muted:#4a5580;
  --red:#ff4757; --green:#00ff88; --orange:#ff9f43; --yellow:#ffd32a;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Satoshi',sans-serif;background:var(--bg-dark);color:var(--text-primary);min-height:100vh;}

@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
@keyframes slideIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
@keyframes glow-pulse{0%,100%{box-shadow:0 0 10px rgba(0,212,255,.2)}50%{box-shadow:0 0 28px rgba(0,212,255,.5),0 0 50px rgba(0,212,255,.15)}}
@keyframes scanline-move{from{background-position:0 0}to{background-position:0 100%}}

.scanline-overlay{
  position:fixed;inset:0;pointer-events:none;z-index:999;
  background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,212,255,.012) 3px,rgba(0,212,255,.012) 4px);
  animation:scanline-move 8s linear infinite;
}

.reg-page{
  min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;
  background:
    radial-gradient(ellipse at 25% 30%,rgba(0,212,255,.06) 0%,transparent 55%),
    radial-gradient(ellipse at 75% 75%,rgba(107,93,255,.06) 0%,transparent 55%),
    var(--bg-dark);
}

.reg-card{
  background:var(--bg-card);border:1px solid rgba(255,255,255,.06);
  border-radius:2.5rem;padding:44px 40px;width:100%;max-width:460px;
  position:relative;overflow:hidden;animation:fadeIn .5s ease;
}
.reg-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;
  background:linear-gradient(90deg,transparent,var(--neon-cyan),var(--neon-purple),transparent);}

.reg-logo{text-align:center;margin-bottom:28px;}
.reg-logo .icon{
  width:58px;height:58px;margin:0 auto 14px;
  background:linear-gradient(135deg,rgba(0,212,255,.12),rgba(107,93,255,.12));
  border:1.5px solid rgba(0,212,255,.3);border-radius:50%;
  display:flex;align-items:center;justify-content:center;font-size:24px;
  animation:glow-pulse 3s ease-in-out infinite;
}
.reg-logo h1{font-family:'Orbitron',monospace;font-size:22px;font-weight:700;}
.reg-logo h1 span{color:var(--neon-cyan);}
.reg-logo p{font-size:11px;color:var(--text-muted);margin-top:5px;font-family:'Rajdhani',sans-serif;letter-spacing:.1em;}

/* Success state */
.success-box{
  background:rgba(0,255,136,.06);border:1px solid rgba(0,255,136,.25);
  border-radius:1rem;padding:24px;text-align:center;margin-bottom:20px;animation:fadeIn .4s ease;
}
.success-box .check{font-size:44px;margin-bottom:12px;}
.success-box h3{font-family:'Cabinet Grotesk',sans-serif;color:var(--green);font-size:20px;font-weight:800;}
.success-box p{font-size:13px;color:var(--text-muted);margin-top:8px;}

/* Error alert */
.error-alert{
  background:rgba(255,71,87,.08);border:1px solid rgba(255,71,87,.3);border-left:3px solid var(--red);
  color:#fca5a5;padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:18px;
  animation:slideIn .3s ease;
}

/* Form inputs */
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

/* Strength bar */
.strength-bar{height:3px;background:rgba(255,255,255,.08);border-radius:2px;margin-top:7px;overflow:hidden;}
.strength-fill{height:100%;border-radius:2px;transition:all .3s ease;width:0;}
.strength-label{font-size:11px;font-family:'Cabinet Grotesk',sans-serif;margin-top:5px;color:var(--text-muted);}
.match-hint{font-size:11px;font-family:'Cabinet Grotesk',sans-serif;margin-top:5px;}

/* Role info */
.role-info{
  background:rgba(0,212,255,.04);border:1px solid rgba(0,212,255,.15);border-left:3px solid var(--neon-cyan);
  border-radius:10px;padding:12px 14px;margin-bottom:20px;font-size:12px;color:var(--text-muted);line-height:1.8;
}
.role-info strong{color:var(--neon-cyan);}

/* Submit button */
.btn-gradient{
  width:100%;padding:14px;border:none;cursor:pointer;
  background:linear-gradient(135deg,var(--neon-cyan) 0%,var(--neon-purple) 100%);
  color:#fff;font-family:'Rajdhani',sans-serif;font-size:16px;font-weight:700;
  text-transform:uppercase;letter-spacing:.08em;border-radius:50px;
  box-shadow:0 4px 15px rgba(0,212,255,.3);transition:all .3s;
  position:relative;overflow:hidden;display:block;text-decoration:none;text-align:center;
}
.btn-gradient::before{content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,var(--neon-purple),var(--neon-cyan));opacity:0;transition:opacity .3s;}
.btn-gradient span{position:relative;z-index:1;}
.btn-gradient:hover{transform:translateY(-3px);box-shadow:0 8px 25px rgba(0,212,255,.45);}
.btn-gradient:hover::before{opacity:1;}

.back-link{text-align:center;margin-top:18px;font-size:13px;color:var(--text-muted);}
.back-link a{color:var(--neon-cyan);font-weight:600;text-decoration:none;}
</style>
</head>
<body>
<div class="scanline-overlay"></div>

<div class="reg-page">
  <div class="reg-card">
    <div class="reg-logo">
      <div class="icon">🛡️</div>
      <h1>Cyber<span> Incident System</span></h1>
      <p>// REGISTER NEW OPERATOR ACCOUNT</p>
    </div>

    <?php if ($success): ?>
      <div class="success-box">
        <div class="check">✅</div>
        <h3>Account Created!</h3>
        <p>Welcome aboard, <strong><?= htmlspecialchars($new_name) ?></strong>.<br>You can now log in with your credentials.</p>
      </div>
      <a href="index.php" class="btn-gradient"><span>Go to Login →</span></a>

    <?php else: ?>

      <?php if ($error): ?>
        <div class="error-alert">⚠ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="register.php" novalidate>

        <div class="form-group">
          <label class="form-label">Full Name</label>
          <div class="input-wrap">
            <span class="input-icon">👤</span>
            <input class="form-input" type="text" name="name"
                   placeholder="e.g. Alex Johnson"
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                   required autofocus>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-wrap">
            <span class="input-icon">✉</span>
            <input class="form-input" type="email" name="email"
                   placeholder="operator@company.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔑</span>
            <input class="form-input" type="password" name="password" id="pw1"
                   placeholder="Min. 6 characters" required
                   oninput="checkStrength(this.value)"
                   style="padding-right:44px;">
            <button type="button" class="toggle-pw" onclick="togglePw('pw1',this)">👁</button>
          </div>
          <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
          <div class="strength-label" id="strength-label"></div>
        </div>

        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input class="form-input" type="password" name="confirm_password" id="pw2"
                   placeholder="Re-enter password" required
                   oninput="checkMatch()"
                   style="padding-right:44px;">
            <button type="button" class="toggle-pw" onclick="togglePw('pw2',this)">👁</button>
          </div>
          <div class="match-hint" id="match-hint"></div>
        </div>

        <div class="role-info">
          <strong>ℹ ACCOUNT ROLE</strong><br>
          New accounts are <strong>Employee</strong> by default.<br>
          Admin access must be granted by a system administrator.
        </div>

        <button type="submit" class="btn-gradient"><span>Create Account →</span></button>
      </form>

      <div class="back-link">
        Already registered? <a href="index.php">Sign In →</a>
      </div>

    <?php endif; ?>
  </div>
</div>

<script src="js/main.js"></script>
</body>
</html>