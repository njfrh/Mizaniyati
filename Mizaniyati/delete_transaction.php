<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header('Location: reports.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$transaction_id = intval($_POST['id']);

// 1. جلب تفاصيل العملية المحذوفة (المبلغ ونوع الحساب)
$stmt = $conn->prepare("SELECT amount, account_type FROM transactions WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$transaction = $result->fetch_assoc();
$stmt->close();

if ($transaction) {
    $amount_to_reverse = $transaction['amount'];
    $account_type = $transaction['account_type'];
    
    // 🛑 2. عكس العملية على رصيد الحساب:
    // إذا كانت العملية مصروف (-100)، نعكسها بإضافة (+100).
    // إذا كانت العملية إيراد (+100)، نعكسها بطرح (-100).
    $reverse_amount = -$amount_to_reverse;
    
    // جلب الرصيد الحالي
    $stmt = $conn->prepare("SELECT balance FROM accounts WHERE user_id = ? AND account_type = ?");
    $stmt->bind_param("is", $user_id, $account_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $current_balance = $row['balance'] ?? 0;
    $stmt->close();
    
    // تحديث الرصيد
    $new_balance = $current_balance + $reverse_amount;
    
    // تحديث الرصيد في قاعدة البيانات
    $update_stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = ?");
    $update_stmt->bind_param("dis", $new_balance, $user_id, $account_type);
    $update_stmt->execute();
    $update_stmt->close();
    
    // 🛑 3. حذف العملية من جدول transactions
    $delete_stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $delete_stmt->bind_param("ii", $transaction_id, $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();
}

// العودة إلى صفحة التقارير بعد الحذف
header('Location: reports.php');
exit;
?>