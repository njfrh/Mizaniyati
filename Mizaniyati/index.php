<?php
session_start();

// إذا المستخدم مسجّل دخول
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard1.php");
    exit;
}

// إذا مو مسجّل دخول
header("Location: auth.php?tab=login");
exit;
?>