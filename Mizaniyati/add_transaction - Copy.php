 <?php
session_start();
include 'db.php'; // الاتصال بقاعدة البيانات

// تأكد من أن المستخدم مسجل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

// معالجة طلبات AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['section'])) {
    $user_id = $_SESSION['user_id']; // أخذ user_id من الجلسة
    $section = $_POST['section']; // القسم (monthly, daily)
    $category = $_POST['category']; // الفئة (مثل ملابس, مطاعم)
    $action = $_POST['action']; // الإجراء (add أو subtract)
    $amount = floatval($_POST['amount'] ?? 0); // المبلغ
    $comment = $_POST['comment'] ?? ''; // التعليق

    if ($amount > 0) {
        // إذا كان الإجراء هو subtract، تحويل المبلغ إلى قيمة سالبة
        if ($action === 'subtract') $amount = -$amount;

        // إدخال المعاملة في قاعدة البيانات
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, section, category, amount, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issds", $user_id, $section, $category, $amount, $comment);
        $stmt->execute();
    }

    // جلب الرصيد الحالي من قاعدة البيانات
    $stmt = $conn->prepare("SELECT balance FROM accounts WHERE user_id = ? AND account_type = 'إجمالي'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_balance = $row['balance'] ?? 0;

    // تعديل الرصيد بناءً على العملية (إضافة أو خصم)
    $total_balance += $amount;

    // تأكد من عدم أن يكون الرصيد سالبًا
    $total_balance = max(0, $total_balance);

    // تحديث الرصيد في قاعدة البيانات
    $update_stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = 'إجمالي'");
    $update_stmt->bind_param("di", $total_balance, $user_id);
    $update_stmt->execute();

    // إعادة التوجيه إلى صفحة الحسابات بعد التحديث
    header("Location: dashboard1.php"); // التوجيه إلى صفحة الحسابات بعد المعاملة
    exit;
}
?>