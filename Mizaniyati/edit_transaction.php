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
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_amount = floatval($_POST['amount'] ?? 0);
    $new_comment = trim($_POST['comment'] ?? '');
    $new_account_type = $_POST['account_type'] ?? 'إجمالي';

    if ($new_amount <= 0 || $new_comment === '') {
        $error_message = "يرجى إدخال مبلغ صحيح وتعليق.";
    } else {
        $old_amount = $transaction['amount'];
        $old_account_type = $transaction['account_type'];
        
        // 1. عكس العملية القديمة على الحساب القديم (إرجاع الرصيد إلى ما قبل العملية القديمة)
        $reverse_amount = -$old_amount;
        
        // جلب رصيد الحساب القديم وتحديثه
        $stmt_old_account = $conn->prepare("SELECT balance FROM accounts WHERE user_id = ? AND account_type = ?");
        $stmt_old_account->bind_param("is", $user_id, $old_account_type);
        $stmt_old_account->execute();
        $old_row = $stmt_old_account->get_result()->fetch_assoc();
        $old_balance = $old_row['balance'] ?? 0;
        $stmt_old_account->close();

        $new_old_balance = $old_balance + $reverse_amount;
        $new_old_balance = max(0, $new_old_balance);
        
        $update_old_stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = ?");
        $update_old_stmt->bind_param("dis", $new_old_balance, $user_id, $old_account_type);
        $update_old_stmt->execute();
        $update_old_stmt->close();


        // 2. تطبيق العملية الجديدة على الحساب الجديد
        $final_new_amount = -$new_amount; // دائماً مصروف في هذا النموذج
        
        // جلب رصيد الحساب الجديد وتحديثه
        $stmt_new_account = $conn->prepare("SELECT balance FROM accounts WHERE user_id = ? AND account_type = ?");
        $stmt_new_account->bind_param("is", $user_id, $new_account_type);
        $stmt_new_account->execute();
        $new_row = $stmt_new_account->get_result()->fetch_assoc();
        $new_balance = $new_row['balance'] ?? 0;
        $stmt_new_account->close();
        
        $final_new_balance = $new_balance + $final_new_amount;
        $final_new_balance = max(0, $final_new_balance);

        $update_new_stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = ?");
        $update_new_stmt->bind_param("dis", $final_new_balance, $user_id, $new_account_type);
        $update_new_stmt->execute();
        $update_new_stmt->close();


        // 3. تحديث العملية في جدول transactions
        $update_trans_stmt = $conn->prepare("UPDATE transactions SET amount = ?, comment = ?, account_type = ? WHERE id = ? AND user_id = ?");
        $update_trans_stmt->bind_param("dssii", $final_new_amount, $new_comment, $new_account_type, $transaction_id, $user_id);
        $update_trans_stmt->execute();
        $update_trans_stmt->close();

        // التوجيه إلى صفحة التقارير بعد نجاح التعديل
        header("Location: reports.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تعديل العملية</title>
<link rel="stylesheet" href="style.css"> <style>
    /* ... (يمكنك وضع تنسيقات CSS هنا إذا لم يكن لديك ملف style.css) ... */
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
        <h2>✏️ تعديل العملية رقم: <?= htmlspecialchars($transaction_id) ?></h2>

        <?php if ($error_message): ?>
            <p class="error"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <form method="post" class="edit-form">
            <input type="hidden" name="id" value="<?= htmlspecialchars($transaction_id) ?>">

            <label for="amount-input">المبلغ:</label>
            <input type="number" name="amount" id="amount-input" value="<?= htmlspecialchars(abs($transaction['amount'])) ?>" min="0.01" step="0.01" required>

            <label for="comment-input">التعليق:</label>
            <input type="text" name="comment" id="comment-input" value="<?= htmlspecialchars($transaction['comment']) ?>" required>
            
            <label for="account-select">الحساب:</label>
            <select name="account_type" id="account-select" required>
                <?php 
                    $accounts = ['إجمالي', 'ترفيه', 'مغلق'];
                    foreach($accounts as $acc):
                        $selected = ($acc === $transaction['account_type']) ? 'selected' : '';
                ?>
                <option value="<?= $acc ?>" <?= $selected ?>><?= $acc ?></option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit">حفظ التعديلات</button>
            <p style="text-align: center; margin-top: 15px;"><a href="reports.php" style="color: #555;">إلغاء والعودة للتقارير</a></p>
        </form>
    </div>
</body>
</html>