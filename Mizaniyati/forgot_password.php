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
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
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
<head><meta charset="utf-8"><title>Forgot password</title></head>
<body>
  <h2>Reset your password</h2>
  <?php if ($errors): ?><div style="color:red"><?=implode('<br>', $errors)?></div><?php endif; ?>
  <form method="post">
    <label>Email</label><br>
    <input type="email" name="email" required><br><br>
    <button type="submit">Continue</button>
  </form>
  <p><a href="auth.php?tab=login">Back to login</a></p>
</body>
</html>