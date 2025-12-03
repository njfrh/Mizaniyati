<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header('Location: reports.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$transaction_id = intval($_POST['id']);

// جلب العملية فقط للتأكد أنها تخص المستخدم
$stmt = $conn->prepare("SELECT id FROM transactions WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$transaction = $result->fetch_assoc();
$stmt->close();

if ($transaction) {

    // ❗ حذف العملية بدون أي تعديل على الرصيد
    $delete_stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $delete_stmt->bind_param("ii", $transaction_id, $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();
}

// رجوع لصفحة التقارير
header('Location: reports.php');
exit;
?>