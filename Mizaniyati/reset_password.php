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

  if (strlen($p1) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
  } elseif ($p1 !== $p2) {
    $errors[] = 'Passwords do not match.';
 } else {
    $hash = password_hash($p1, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([$hash, $_SESSION['reset_user_id']]);

   
    unset($_SESSION['reset_user_id']);
    $ok = 'Password updated. You can login now.';
  }
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Set new password</title></head>
<body>
  <h2>Set a new password</h2>
  <?php if ($ok): ?>
    <div style="color:green"><?=$ok?></div>
    <p><a href="auth.php?tab=login">Go to login</a></p>
  <?php else: ?>
    <?php if ($errors): ?><div style="color:red"><?=implode('<br>', $errors)?></div><?php endif; ?>
<form method="post">
      <label>New password</label><br>
      <input type="password" name="password" minlength="8" required><br><br>
      <label>Confirm password</label><br>
      <input type="password" name="password_confirm" minlength="8" required><br><br>
      <button type="submit">Update password</button>
    </form>
  <?php endif; ?>
</body>
</html>