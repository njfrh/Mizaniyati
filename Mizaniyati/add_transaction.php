<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['account_type'])) {
    $user_id = $_SESSION['user_id']; 
    
    // ✅ القيمة الجديدة التي تحدد نوع الشراء (ضروري، يومي، شهري)
    $transaction_category = $_POST['account_type']; 
    
    // تحديد الحساب الفعلي الذي سيتم السحب منه (نعتبر الإجمالي هو الافتراضي للمشتريات)
    // نعتبر أن الحساب الفعلي هو 'إجمالي' ما لم يتم تحديده بوضوح كـ 'مغلق' (إذا كان لديك حقل آخر يحدد الحساب الفعلي)
    // بما أننا لا نملك حقل إضافي يحدد الحساب الفعلي، سنفترض أن كل مشترياتك تتم من 'الإجمالي'
    $actual_account_type = 'إجمالي'; 
    if ($transaction_category === 'مغلق') {
        $actual_account_type = 'مغلق';
    } 

    $action = $_POST['action'] ?? 'subtract'; 
    $amount = floatval($_POST['amount'] ?? 0); 
    $comment = $_POST['comment'] ?? ''; 
    $created_at = date('Y-m-d H:i:s'); 

    // جلب ID الحساب الفعلي
    $stmt = $conn->prepare("SELECT id, balance FROM accounts WHERE user_id = ? AND account_type = ?");
    $stmt->bind_param("is", $user_id, $actual_account_type); 
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $account_id = $row['id']; 
        $current_balance = $row['balance'];
    } else {
        // إذا لم يتم العثور على الحساب، نعود للصفحة الرئيسية
        header("Location: dashboard1.php");
        exit;
    }
    $stmt->close();
    
    
    if ($amount > 0 && $account_id !== null) {
        if ($action === 'subtract') $amount = -$amount;

        // 🛑 التعديل هنا: استخدام $transaction_category كـ account_type في جدول transactions
        // وهي تمثل الآن نوع المشتريات (ضروري، يومي، شهري)
        $columns = "user_id, account_id, amount, account_type, comment, created_at";
        $stmt_insert = $conn->prepare("INSERT INTO transactions ({$columns}) VALUES (?, ?, ?, ?, ?, ?)");
        
        // المتغيرات المرتبطة: i (user_id), i (account_id), d (amount), s (transaction_category), s (comment), s (created_at)
        $stmt_insert->bind_param("iidsss", 
            $user_id, 
            $account_id, 
            $amount, 
            $transaction_category, // نوع المشتريات الجديد
            $comment, 
            $created_at
        );
        
        $stmt_insert->execute();
        $stmt_insert->close();
    }

    // 🛑 تحديث الرصيد في قاعدة البيانات للحساب الفعلي ($actual_account_type)
    $new_balance = $current_balance + $amount;
    $new_balance = max(0, $new_balance); // عدم السماح بالرصيد السالب
    
    $update_stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = ?");
    $update_stmt->bind_param("dis", $new_balance, $user_id, $actual_account_type);
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