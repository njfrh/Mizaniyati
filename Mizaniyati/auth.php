<?php
session_start();
require_once 'db.php';

$tab = $_GET['tab'] ?? 'login'; 
$errors = [];
$notice = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'register') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
      $errors[] = 'All fields are required.';
      $tab = 'register';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Invalid email.';
      $tab = 'register';
    } elseif (strlen($password) < 8) {
      $errors[] = 'Password must be at least 8 characters.';
      $tab = 'register';
    } else {
     
      $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
      $stmt->execute([$email]);
      if ($stmt->fetch()) {
        $errors[] = 'Email already registered. Please login.';
        $tab = 'login';
 } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)');
        $stmt->execute([$name, $email, $hash]);
       
        $notice = 'Account created successfully. Please login.';
        $tab = 'login';
      }
    }

  } elseif ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
      $errors[] = 'Email and password are required.';
      $tab = 'login';
    } else {
     $stmt = $pdo->prepare('SELECT id, name, password_hash FROM users WHERE name = ? LIMIT 1');
     $stmt->execute([$username]);
      $user = $stmt->fetch();
      if (!$user || !password_verify($password, $user['password_hash'])) {
        $errors[] = 'Invalid email or password.';
        $tab = 'login';
      } else {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header('Location: setup_account.php');
        exit;
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Mizaniyati — Auth</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    * { box-sizing: border-box; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; }
    body { background:#f4f5f7; margin:0; display:flex; min-height:100vh; align-items:center; justify-content:center; }
    .card { width: 360px; background:#fff; border-radius:14px; padding:22px 20px 26px; box-shadow:0 8px 24px rgba(0,0,0,.08); }
    .brand { text-align:center; font-weight:800; font-size:22px; margin-bottom:6px; }
    .tabs { display:flex; gap:6px; background:#f0f2f5; padding:6px; border-radius:10px; margin:10px 0 18px; }
    .tab-btn { flex:1; border:0; padding:10px; border-radius:8px; background:transparent; cursor:pointer; font-weight:600; }
    .tab-btn.active { background:#101826; color:#fff; }
    .hint { text-align:center; color:#666; font-size:12px; margin-bottom:14px; }
    .field { margin-bottom:12px; }
    label { display:block; font-size:12px; color:#333; margin-bottom:6px; }
    input { width:100%; height:40px; border:1px solid #dcdfe4; border-radius:8px; padding:0 12px; }
    .btn { width:100%; height:42px; border:0; border-radius:10px; background:#101826; color:#fff; font-weight:700; cursor:pointer; }
    .alt { text-align:center; font-size:12px; margin-top:10px; }
    .err { background:#ffe9e9; color:#a40000; padding:10px; border-radius:8px; margin-bottom:10px; font-size:13px; }
    .ok { background:#e8fff1; color:#0b7a3b; padding:10px; border-radius:8px; margin-bottom:10px; font-size:13px; }
 .hidden { display:none; }
  </style>
</head>
<body>
  <div class="card">
    <div class="brand">ميزانيتي</div>

    <?php if ($notice): ?><div class="ok"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="err"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div><?php endif; ?>

    <div class="tabs">
      <button class="tab-btn <?= $tab==='register'?'active':'' ?>" data-tab="register">Create account</button>
      <button class="tab-btn <?= $tab==='login'?'active':'' ?>" data-tab="login">Login</button>
    </div>
<!-- Register Form -->
    <form id="tab-register" method="post" class="<?= $tab==='register'?'':'hidden' ?>">
      <input type="hidden" name="action" value="register">
      <div class="hint">Enter your username, email and password.</div>
      <div class="field">
        <label>Username</label>
        <input name="name" required>
      </div>
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" minlength="8" required>
      </div>
      <button class="btn" type="submit">Create an account</button>
    </form>

      <form id="tab-login" method="post" action="setup_account.php" class="<?= $tab==='login'?'':'hidden' ?>">
      <input type="hidden" name="action" value="login">
      <div class="hint">Enter your username, password.</div>
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" required>
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button class="btn" type="submit">Continue</button>
      <div class="alt"><a href="forgot_password.php">Forgot password?</a></div>
    </form>
  </div>

  <script>
   
    const tabs = document.querySelectorAll('.tab-btn');
    const reg = document.getElementById('tab-register');
    const log = document.getElementById('tab-login');
tabs.forEach(btn => {
      btn.addEventListener('click', () => {
        tabs.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const t = btn.dataset.tab;
        if (t === 'register') { reg.classList.remove('hidden'); log.classList.add('hidden'); history.replaceState(null,'','?tab=register'); }
        else { log.classList.remove('hidden'); reg.classList.add('hidden'); history.replaceState(null,'','?tab=login'); }
      });
    });
  </script>
</body>
</html>
