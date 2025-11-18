 <?php
session_start();
include "db.php"; // الاتصال بقاعدة البيانات

$user_id = $_SESSION['user_id'] ?? 1;

// دالة لجلب الرصيد
function get_balance($conn, $user_id, $account_type) {
    $stmt = $conn->prepare("SELECT balance FROM accounts WHERE user_id = ? AND account_type = ?");
    $stmt->bind_param("is", $user_id, $account_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // إذا لم يوجد حساب، يتم إنشاؤه برصيد 0
        $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_type, balance) VALUES (?, ?, 0)");
        $stmt->bind_param("is", $user_id, $account_type);
        $stmt->execute();
        return 0;
    }
    
    return (float)$result->fetch_assoc()['balance'];
}

$success_message = '';
$error_message   = '';

/* ================== معالجة الصرف من الحساب المغلق ================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action  = $_POST['action'] ?? '';
    $amount  = abs(floatval($_POST['amount'] ?? 0));
    $comment = $_POST['comment'] ?? '';

    if ($action === 'subtract') {

        if ($amount <= 0) {
            $error_message = "الرجاء إدخال مبلغ صحيح.";
        } else {

            // جلب الرصيد الحالي في الحساب المغلق
            $locked_balance = get_balance($conn, $user_id, 'مغلق');

            if ($amount > $locked_balance) {
                $error_message = "المبلغ أكبر من رصيد الحساب المغلق.";
            } else {
                // خصم من رصيد الحساب المغلق
                $locked_balance -= $amount;

                // تحديث رصيد حساب مغلق في جدول accounts
                $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = 'مغلق'");
                $stmt->bind_param("di", $locked_balance, $user_id);
                $stmt->execute();
                $stmt->close();

                // تسجيل العملية في جدول transactions
                $stmt = $conn->prepare("INSERT INTO transactions (user_id, account_type, amount, comment) VALUES (?, 'مغلق', ?, ?)");
                $stmt->bind_param("ids", $user_id, $amount, $comment);
                $stmt->execute();
                $stmt->close();

                $success_message = "تم الصرف من الحساب المغلق بنجاح.";
            }
        }
    }
}

/* ================== حساب الشروط وعرض الصفحة ================== */

// جلب الراتب/الرصيد الإجمالي
$salary           = get_balance($conn, $user_id, 'إجمالي');
// حساب 30% من الراتب
$required_balance = $salary * 0.30;
// جلب الرصيد الحالي في الحساب المغلق (بعد أي خصم لو صار)
$locked_balance   = get_balance($conn, $user_id, 'مغلق');

// التحقق من شرط فتح الحساب المغلق
if ($locked_balance >= $required_balance) {
    $can_access_locked = true;
    $condition_message = "يمكنك الآن الدخول إلى الحساب المغلق.";
} else {
    $can_access_locked = false;
    $condition_message = "لا يمكنك الدخول إلى الحساب المغلق حتى يصل رصيدك إلى " . number_format($required_balance, 0) . " ريال.";
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>حساب مغلق</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            direction: rtl;
            background-color: #f5f5f5;
            padding: 20px;
            margin: 0;
        }
        .back-link {
            display: inline-block;
            margin: 10px 20px;
            text-decoration: none;
            color: #007bff;
            font-size: 14px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .container {
            background: white;
            width: 50%;
            margin: 0 auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .message {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .message.condition {
            color: red;
        }
        .message.error {
            color: #c0392b;
        }
        .message.success {
            color: #27ae60;
        }
        .balance-info {
            font-size: 18px;
            margin-bottom: 15px;
        }
        .input-group {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        input[type="number"], input[type="text"] {
            padding: 8px;
            font-size: 16px;
            width: 45%;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .submit-btn {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        .submit-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

    <!-- زر الرجوع إلى صفحة الرصيد الإجمالي -->
    <a href="dashboard1.php" class="back-link">← الرجوع إلى الرصيد الإجمالي</a>

    <div class="container">
        <h2>حسابك المغلق 🛑</h2>

        <div class="message condition"><?= $condition_message ?></div>

        <?php if ($error_message): ?>
            <div class="message error"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="message success"><?= $success_message ?></div>
        <?php endif; ?>

        <?php if ($can_access_locked): ?>
            <div class="balance-info">
                رصيدك في الحساب المغلق: SAR <?= number_format($locked_balance, 0) ?><br>
                30% من راتبك: SAR <?= number_format($required_balance, 0) ?>
            </div>

            <!-- نفس الصفحة، مافي locked_process.php -->
            <form method="post" action="">
                <div class="input-group">
                    <input type="number" name="amount" placeholder="المبلغ للصرف" required>
                    <input type="text" name="comment" placeholder="التعليق (مثلاً: سفر، طوارئ...)" required>
                </div>
                <button class="submit-btn" type="submit" name="action" value="subtract">صرف</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>