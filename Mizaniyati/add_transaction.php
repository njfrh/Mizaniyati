<?php
session_start();
date_default_timezone_set('Asia/Riyadh');
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['account_type'])) {
    $user_id = $_SESSION['user_id']; 
    
    // نوع المشتريات (ضرورية، يومية، شهرية، أو "مغلق")
    $transaction_category = $_POST['account_type']; 
    
    // الحساب الفعلي اللي بنسحب منه
    $actual_account_type = 'إجمالي'; 
    if ($transaction_category === 'مغلق') {
        $actual_account_type = 'مغلق';
    } 

    $action     = $_POST['action'] ?? 'subtract'; 
    $amount     = floatval($_POST['amount'] ?? 0); 
    $comment    = $_POST['comment'] ?? ''; 
    $created_at = date('Y-m-d H:i:s'); 

    // جلب الحساب الفعلي (إجمالي أو مغلق)
    $stmt = $conn->prepare("SELECT id, balance FROM accounts WHERE user_id = ? AND account_type = ?");
    $stmt->bind_param("is", $user_id, $actual_account_type); 
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $account_id      = $row['id']; 
        $current_balance = (float)$row['balance'];
    } else {
        // ما فيه حساب بهالنوع
        header("Location: dashboard1.php");
        exit;
    }
    $stmt->close();


    // ==============================
    // 🛑 منع الصرف لو الرصيد ما يكفي
    // ==============================
    if ($action === 'subtract') {

        // رصيد صفر أو أقل
        if ($current_balance <= 0) {
            $_SESSION['error'] = "ما تقدر تصرف، رصيدك لهذا الحساب صفر.";
            header("Location: reports.php");
            exit;
        }

        // مبلغ أكبر من الرصيد
        if ($amount > $current_balance) {
            $_SESSION['error'] = "المبلغ أكبر من رصيدك المتاح في هذا الحساب.";
            header("Location: reports.php");
            exit;
        }

        // لو سمحنا بالصرف، نخلي المبلغ بالسالب عشان ينقص الرصيد
        $amount = -$amount;
    }

    // ==============================
    // 📌 إدخال العملية في جدول transactions
    // ==============================
    if ($amount != 0 && !empty($account_id)) {

        $columns = "user_id, account_id, amount, account_type, comment, created_at";
        $stmt_insert = $conn->prepare("INSERT INTO transactions ({$columns}) VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt_insert->bind_param(
            "iidsss", 
            $user_id, 
            $account_id, 
            $amount,              // هنا المبلغ يكون سالب لو صرف
            $transaction_category, // ضرورية / يومية / شهرية / مغلق
            $comment, 
            $created_at
        );
        
        $stmt_insert->execute();
        $stmt_insert->close();
    }

    // ==============================
    // 📌 تحديث رصيد الحساب الفعلي
    // ==============================
    $new_balance = $current_balance + $amount; // لو صرف = ينقص، لو دخل = يزيد
    $new_balance = max(0, $new_balance);       // ما نخلي الرصيد بالسالب أبدًا
    
    $update_stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = ?");
    $update_stmt->bind_param("dis", $new_balance, $user_id, $actual_account_type);
    $update_stmt->execute();
    $update_stmt->close();

    // نرجع لصفحة التقارير
    header("Location: reports.php"); 
    exit;

} else {
    header("Location: dashboard1.php");
    exit;
}
?>