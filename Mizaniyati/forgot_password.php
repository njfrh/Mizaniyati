<?php
session_start();
require_once 'db.php';

$errors = [];
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $u = $result->fetch_assoc();
        $stmt->close();

        if (!$u) {
            $errors[] = 'Email not found.';
        } else {
            $_SESSION['reset_user_id'] = $u['id'];
            header('Location: reset_password.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Forgot password</title>

<style>
* {
  box-sizing: border-box;
  font-family: "Cairo", system-ui, -apple-system, Segoe UI, Roboto, Arial;
}

body {
  margin: 0;
  background: linear-gradient(135deg, #2AB7A9, #1E8E82 65%);
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: 20px;
}

.card {
  width: 380px;
  background: #fff;
  border-radius: 22px;
  padding: 32px 28px;
  box-shadow: 0 12px 35px rgba(0,0,0,0.15);
  animation: fadeIn .5s ease;
}

.brand {
  text-align: center;
  font-weight: 800;
  font-size: 28px;
  margin-bottom: 14px;
  color: #116B63;
}

.hint {
  text-align: center;
  color: #667;
  font-size: 14px;
  margin-bottom: 18px;
}

label {
  display: block;
  font-size: 13px;
  color: #116B63;
  font-weight: 600;
  margin-bottom: 6px;
}

input {
  width: 100%;
  height: 46px;
  border: 1.7px solid #c8e9e6;
  border-radius: 12px;
  padding: 0 14px;
  font-size: 15px;
  transition: .25s;
  outline: none;
}

input:focus {
  border-color: #2AB7A9;
  box-shadow: 0 0 0 3px rgba(42,183,169,0.20);
}

.btn {
  width: 100%;
  height: 48px;
  border: 0;
  border-radius: 12px;
  background: #2AB7A9;
  color: #fff;
  font-size: 17px;
  font-weight: 800;
  cursor: pointer;
  transition: .25s;
  margin-top: 12px;
}

.btn:hover {
  background: #1E8E82;
}

.err {
  padding: 12px;
  background: #ffe8e8;
  border: 1px solid #ffb9b9;
  color: #b10000;
  border-radius: 10px;
  font-size: 14px;
  margin-bottom: 12px;
}

.back {
  text-align: center;
  margin-top: 18px;
}

.back a {
  color: #2AB7A9;
  text-decoration: none;
  font-weight: 700;
}

.back a:hover {
  text-decoration: underline;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(15px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>

</head>
<body>

<div class="card">

    <div class="brand">Forgot Password</div>
    <div class="hint">Enter your email to reset your password</div>

    <?php if ($errors): ?>
        <div class="err"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Email</label>
        <input type="email" name="email" required>

        <button class="btn" type="submit">Continue</button>
    </form>

    <div class="back">
        <a href="auth.php?tab=login">Back to login</a>
    </div>

</div>

</body>
</html>
