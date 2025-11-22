<?php
session_start();
include 'db.php'; // الاتصال بقاعدة البيانات

// تأكد من أن المستخدم مسجل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['section'])) {
    $user_id = $_SESSION['user_id']; 
    // هذه المتغيرات لم نعد نستخدمها في INSERT، لكن قد نحتاجها لاحقًا
    $section = $_POST['section'] ?? 'يومية'; 
    $category = $_POST['category'] ?? 'أخرى'; 
    
    $action = $_POST['action']; 
    $amount = floatval($_POST['amount'] ?? 0); 
    $comment = $_POST['comment'] ?? ''; 
    
    // ✅ جلب نوع الحساب الذي اختاره المستخدم
    $account_type = $_POST['account_type'] ?? 'إجمالي'; 

    // إعداد متغير الوقت والتاريخ
    $created_at = date('Y-m-d H:i:s'); 
    $account_id = null;
    $current_balance = 0;

    // 🛑 1. جلب رصيد و ID الحساب المختار لتحديثه ولإدراجه في سجل العمليات
    $stmt = $conn->prepare("SELECT id, balance FROM accounts WHERE user_id = ? AND account_type = ?");
    $stmt->bind_param("is", $user_id, $account_type); 
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $account_id = $row['id']; 
        $current_balance = $row['balance'];
    }
    $stmt->close();
    
    
    if ($amount > 0 && $account_id !== null) {
        if ($action === 'subtract') $amount = -$amount;

        // 🛑 2. تنفيذ استعلام INSERT مع الأعمدة الستة الجديدة
        $columns = "user_id, account_id, amount, account_type, comment, created_at";
        $stmt_insert = $conn->prepare("INSERT INTO transactions ({$columns}) VALUES (?, ?, ?, ?, ?, ?)");
        
        // 🛑 ربط المتغيرات (iidsss): i (user_id), i (account_id), d (amount), s (account_type), s (comment), s (created_at)
        $stmt_insert->bind_param("iidsss", 
            $user_id, 
            $account_id, 
            $amount, 
            $account_type, 
            $comment, 
            $created_at
        );
        
        $stmt_insert->execute();
        $stmt_insert->close();
    }

    // 🛑 3. تحديث الرصيد في قاعدة البيانات للحساب المختار
    $new_balance = $current_balance + $amount;

    if ($action === 'subtract') {
        $new_balance = max(0, $new_balance);
    }
    
    $update_stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = ?");
    $update_stmt->bind_param("dis", $new_balance, $user_id, $account_type);
    $update_stmt->execute();
    $update_stmt->close();

    // التوجيه إلى صفحة التقارير
    header("Location: reports.php"); 
    exit;
} else {
    header("Location: dashboard1.php");
    exit;
}
?>