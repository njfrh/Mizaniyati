<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['reset_user_id'])) {
    header('Location: forgot_password.php');
    exit;
}

$errors = [];
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p1 = $_POST['password'] ?? '';
    $p2 = $_POST['password_confirm'] ?? '';

    // نفس شروط auth.php: أرقام فقط وطول 6 أو أكثر
    if (!preg_match('/^[0-9]{6,}$/', $p1)) {
        $errors[] = 'Password must be numbers only and at least 6 digits.';
    } elseif ($p1 !== $p2) {
        $errors[] = 'Passwords do not match.';
    } else {
        $hash    = password_hash($p1, PASSWORD_BCRYPT);
        $user_id = (int)$_SESSION['reset_user_id'];

        $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->bind_param('si', $hash, $user_id);
        $stmt->execute();
        $stmt->close();

        // بعد التحديث، نحذف السيشن عشان ما يقدر يرجع لنفس الصفحة بدون ما يطلب ريست جديد
        unset($_SESSION['reset_user_id']);
        $ok = 'Password updated. You can login now.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Set new password</title>
    <style>
        .password-wrapper {
            position: relative;
            display: inline-block;
            width: 250px;
        }
        .password-wrapper input {
            width: 100%;
            padding-right: 40px;
            box-sizing: border-box;
        }
        .toggle-password {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            user-select: none;
            font-size: 14px;
        }
    </style>
</head>
<body>
  <h2>Set a new password</h2>

  <?php if ($ok): ?>
    <div style="color:green"><?= htmlspecialchars($ok) ?></div>
    <p><a href="auth.php?tab=login">Go to login</a></p>
  <?php else: ?>
    <?php if ($errors): ?>
      <div style="color:red"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>

    <form method="post">
      <label>New password</label><br>
      <div class="password-wrapper">
          <input type="password"
                 id="new_password"
                 name="password"
                 minlength="6"
                 pattern="[0-9]{6,}"
                 required>
          <span class="toggle-password" id="icon_new"
                onclick="togglePassword('new_password','icon_new')">👁️</span>
      </div>
      <br><br>

      <label>Confirm password</label><br>
      <div class="password-wrapper">
          <input type="password"
                 id="confirm_password"
                 name="password_confirm"
                 minlength="6"
                 pattern="[0-9]{6,}"
                 required>
          <span class="toggle-password" id="icon_confirm"
                onclick="togglePassword('confirm_password','icon_confirm')">👁️</span>
      </div>
      <br><br>

      <button type="submit">Update password</button>
    </form>
  <?php endif; ?>

  <script>
    function togglePassword(fieldId, iconId) {
        const field = document.getElementById(fieldId);
        const icon  = document.getElementById(iconId);
        if (!field) return;

        if (field.type === 'password') {
            field.type = 'text';
            icon.textContent = '🙈';
        } else {
            field.type = 'password';
            icon.textContent = '👁️';
        }
    }
  </script>
</body>
</html>