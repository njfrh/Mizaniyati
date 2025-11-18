 <?php
session_start();
include "db.php";

$user_id = $_SESSION['user_id'] ?? 1;

$type = $_POST['type'] ?? '';
$action = $_POST['action'] ?? '';
$amount = abs(floatval($_POST['amount'] ?? 0));
$comment = $_POST['comment'] ?? '';

if ($type === '' || $amount <= 0) {
    echo json_encode(['error' => 'بيانات غير صالحة']);
    exit;
}

// دالة تجيب الرصيد الحالي
function get_balance($conn, $user_id, $type) {
    $res = $conn->query("SELECT balance FROM accounts WHERE user_id = $user_id AND account_type = '$type'");
    if ($res->num_rows == 0) {
        $conn->query("INSERT INTO accounts (user_id, account_type, balance) VALUES ($user_id, '$type', 0)");
        return 0;
    }
    return (float)$res->fetch_assoc()['balance'];
}

$total_balance = get_balance($conn, $user_id, 'إجمالي');
$current_balance = get_balance($conn, $user_id, $type);

// العمليات
if ($action === 'add') {
    // نقل مبلغ من الإجمالي إلى حساب فرعي
    if ($total_balance >= $amount) {
        $total_balance -= $amount;
        $current_balance += $amount;
    } else {
        echo json_encode(['error' => 'الرصيد الإجمالي غير كافٍ']);
        exit;
    }
} elseif ($action === 'subtract') {
    // خصم من الحساب الفرعي
    if ($current_balance >= $amount) {
        $current_balance -= $amount;
        // ممكن تضيف هنا إدخال سجل صرف في جدول ثاني لو تبغى تسجل التعليقات
    } else {
        echo json_encode(['error' => 'الرصيد في الحساب غير كافٍ']);
        exit;
    }
}

// تحديث قاعدة البيانات
$conn->query("UPDATE accounts SET balance = $total_balance WHERE user_id = $user_id AND account_type = 'إجمالي'");
$conn->query("UPDATE accounts SET balance = $current_balance WHERE user_id = $user_id AND account_type = '$type'");

// إرسال النتائج للجافاسكربت
echo json_encode([
    'success' => true,
    'total' => number_format($total_balance, 0),
    'ترفيه' => number_format(get_balance($conn, $user_id, 'ترفيه'), 0),
    'تسوق' => number_format(get_balance($conn, $user_id, 'تسوق'), 0)
]);