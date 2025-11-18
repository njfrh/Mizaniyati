<?php
session_start();
require_once 'db.php';

$tab     = $_GET['tab'] ?? 'login';
$errors  = [];
$notice  = '';

// ================== معالجة الفورم ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ========== REGISTER ========== */
    if ($action === 'register') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // تحقق من الحقول
        if ($name === '' || $email === '' || $password === '') {
            $errors[] = 'All fields are required.';
            $tab = 'register';

        // اليوزر ممنوع يحتوي أرقام
        } elseif (preg_match('/\d/', $name)) {
            $errors[] = 'Username cannot contain numbers.';
            $tab = 'register';

        // إيميل صحيح
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email.';
            $tab = 'register';

        // الباسورد أرقام فقط وطوله لا يقل عن 6
        } elseif (!preg_match('/^[0-9]{6,}$/', $password)) {
            $errors[] = 'Password must be numbers only and at least 6 digits.';
            $tab = 'register';

        } else {
            // تحقق إذا الإيميل موجود من قبل
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                $errors[] = 'Email already registered. Please login.';
                $tab = 'login';
            } else {
                // إنشاء الحساب
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)');
                $stmt->bind_param('sss', $name, $email, $hash);
                $stmt->execute();
                $stmt->close();

                $notice = 'Account created successfully. Please login.';
                $tab = 'login';
            }
        }

    /* ========== LOGIN ========== */
    } elseif ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $tab = 'login';

        // 1) الحقول مطلوبة
        if ($username === '' || $password === '') {
            $errors[] = 'Username and password are required.';
        }

        // 2) نفس قيود اليوزر نيم: بدون أرقام
        if ($username !== '' && preg_match('/\d/', $username)) {
            $errors[] = 'Username cannot contain numbers.';
        }

        // 3) نفس قيود الباسوورد: أرقام فقط، 6 أو أكثر
        if ($password !== '' && !preg_match('/^[0-9]{6,}$/', $password)) {
            $errors[] = 'Password must be numbers only and at least 6 digits.';
        }

        // لو ما فيه أخطاء → نحاول نسوي تسجيل دخول
        if (empty($errors)) {
            $stmt = $conn->prepare('SELECT id, name, password_hash FROM users WHERE name = ? LIMIT 1');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user   = $result->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'Invalid username or password.';
            } else {
                // نجاح تسجيل الدخول
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];

                // نشيك إذا عنده راتب محفوظ في settings
                $uid   = (int)$user['id'];
                $stmt2 = $conn->prepare("SELECT monthly_salary FROM settings WHERE user_id = ? LIMIT 1");
                $stmt2->bind_param('i', $uid);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                $row2 = $res2->fetch_assoc();
                $stmt2->close();

                if ($row2 && (float)$row2['monthly_salary'] > 0) {
                    // مستخدم قديم → روح مباشرة للداشبورد
                    header('Location: dashboard1.php');
                } else {
                    // أول مرة أو ما ضبط الراتب → روح لصفحة إعداد الحساب
                    header('Location: setup_account.php');
                }
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

    .password-wrapper {
      position: relative;
      width: 100%;
    }
    .password-wrapper input {
      padding-right: 40px;
    }
    .toggle-password {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      user-select: none;
      font-size: 14px;
      color:#555;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="brand">ميزانيتي</div>

    <?php if ($notice): ?>
      <div class="ok"><?= htmlspecialchars($notice) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="err"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>

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
        <div class="password-wrapper">
          <input type="password" id="reg_password" name="password" minlength="6" required>
          <span class="toggle-password" onclick="togglePassword('reg_password', this)">👁️</span>
        </div>
      </div>

      <button class="btn" type="submit">Create an account</button>
    </form>

    <!-- Login Form -->
    <form id="tab-login" method="post" class="<?= $tab==='login'?'':'hidden' ?>">
      <input type="hidden" name="action" value="login">
      <div class="hint">Enter your username, password.</div>

      <div class="field">
        <label>Username</label>
        <input type="text" name="username" required>
      </div>

      <div class="field">
        <label>Password</label>
        <div class="password-wrapper">
          <input type="password" id="login_password" name="password" required>
          <span class="toggle-password" onclick="togglePassword('login_password', this)">👁️</span>
        </div>
      </div>

      <button class="btn" type="submit">Continue</button>
      <div class="alt"><a href="forgot_password.php">Forgot password?</a></div>
    </form>
  </div>

  <script>
    const tabs = document.querySelectorAll('.tab-btn');
    const reg  = document.getElementById('tab-register');
    const log  = document.getElementById('tab-login');

    tabs.forEach(btn => {
      btn.addEventListener('click', () => {
        tabs.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const t = btn.dataset.tab;
        if (t === 'register') {
          reg.classList.remove('hidden');
          log.classList.add('hidden');
          history.replaceState(null, '', '?tab=register');
        } else {
          log.classList.remove('hidden');
          reg.classList.add('hidden');
          history.replaceState(null, '', '?tab=login');
        }
      });
    });

    function togglePassword(inputId, iconEl) {
      const field = document.getElementById(inputId);
      if (!field) return;
      if (field.type === 'password') {
        field.type = 'text';
        iconEl.textContent = '🙈';
      } else {
        field.type = 'password';
        iconEl.textContent = '👁️';
      }
    }
  </script>
</body>
</html>