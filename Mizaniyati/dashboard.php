<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: auth.php?tab=login');
  exit;
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Dashboard</title></head>
<body>
  <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋</h2>
  <p>Logged in successfully.</p>
  <p><a href="logout.php">Logout</a></p>
</body>
</html>