<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php?tab=login');
    exit;
}

$user_id = $_SESSION['user_id'];
$transaction_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

// جلب تفاصيل العملية للتعديل
$stmt = $conn->prepare("SELECT amount, comment, account_type FROM transactions WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$transaction = $result->fetch_assoc();
$stmt->close();

if (!$transaction) {
    header('Location: reports.php');
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $new_amount = floatval($_POST['amount'] ?? 0);
    $new_comment = trim($_POST['comment'] ?? '');
    $new_account_type = $_POST['account_type'] ?? '';

    // 🛑 منع الصفر والسالب
    if ($new_amount <= 0) {
        $error_message = "المبلغ لازم يكون أكبر من صفر.";
    }

    if ($new_comment === '') {
        $error_message = "التعليق مطلوب.";
    }

    if (!$error_message) {

        // المبلغ القديم (يكون سالب لأنه مصروف)
        $old_amount = $transaction['amount'];
        $old_abs = abs($old_amount);

        // الفرق بين القديم والجديد
        $difference = $new_amount - $old_abs;

        // جلب الرصيد الإجمالي
        $bal_stmt = $conn->prepare("SELECT balance FROM accounts WHERE user_id = ? AND account_type = 'إجمالي'");
        $bal_stmt->bind_param("i", $user_id);
        $bal_stmt->execute();
        $row_bal = $bal_stmt->get_result()->fetch_assoc();
        $current_balance = $row_bal['balance'] ?? 0;
        $bal_stmt->close();

        // 🛑 لو الفرق الجديد أكبر من الرصيد → رفض
        if ($difference > $current_balance) {
            $error_message = "المبلغ الجديد أكبر من رصيدك المتاح.";
        } else {

            // 1) رجّعي المبلغ القديم للرصيد
            $balance_after_refund = $current_balance + $old_abs;

            // 2) اخصمي المبلغ الجديد
            $balance_after_update = $balance_after_refund - $new_amount;
            if ($balance_after_update < 0) $balance_after_update = 0;

            // تحديث الرصيد
            $upd_bal = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = 'إجمالي'");
            $upd_bal->bind_param("di", $balance_after_update, $user_id);
            $upd_bal->execute();
            $upd_bal->close();

            // تحديث العملية
            $final_amount = -$new_amount;

            $upd_trans = $conn->prepare("UPDATE transactions SET amount = ?, comment = ?, account_type = ? WHERE id = ? AND user_id = ?");
            $upd_trans->bind_param("dssii", $final_amount, $new_comment, $new_account_type, $transaction_id, $user_id);
            $upd_trans->execute();
            $upd_trans->close();

            header("Location: reports.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تعديل العملية</title>
<link rel="stylesheet" href="style.css">
<style>
    .container { max-width: 600px; margin: 50px auto; padding: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); background-color: #fff; }
    h2 { text-align: center; color: #101826; }
    .edit-form label { display: block; margin-top: 15px; font-weight: 600; }
    .edit-form input[type="number"], .edit-form input[type="text"], .edit-form select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px; }
    .edit-form button { padding: 12px; background: #00a87a; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px; width: 100%; font-weight: bold; }
    .error { color: red; text-align: center; margin-bottom: 10px; }
</style>
</head>
<body> 
    <div class="container">
        <h2>✏️تعديل</h2>

        <?php if ($error_message): ?>
            <p class="error"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <form method="post" class="edit-form">
            <input type="hidden" name="id" value="<?= htmlspecialchars($transaction_id) ?>">

            <label for="amount-input">المبلغ:</label>
            <input type="number" name="amount" id="amount-input" value="<?= htmlspecialchars(abs($transaction['amount'])) ?>" step="1" min="1" required>

            <label for="comment-input">التعليق:</label>
            <input type="text" name="comment" id="comment-input" value="<?= htmlspecialchars($transaction['comment']) ?>" required>
            
            <label for="account-select">التصنيف:</label>
            <select name="account_type" id="account-select" required>
                <option value="ضرورية" <?= $transaction['account_type']=="ضرورية"?"selected":"" ?>>ضرورية</option>
                <option value="يومية" <?= $transaction['account_type']=="يومية"?"selected":"" ?>>يومية</option>
                <option value="شهرية" <?= $transaction['account_type']=="شهرية"?"selected":"" ?>>شهرية</option>
            </select>
            
            <button type="submit">حفظ التعديلات</button>
            <p style="text-align: center; margin-top: 15px;"><a href="reports.php" style="color: #555;">إلغاء والعودة للتقارير</a></p>
        </form>
    </div>
</body>
</html>