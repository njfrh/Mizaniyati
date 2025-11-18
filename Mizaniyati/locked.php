<?php
session_start();
include "db.php";

$user_id = $_SESSION['user_id'] ?? 1;

// ========== دالة تجيب رصيد أي حساب ==========
function get_balance($conn, $user_id, $account_type) {
    $stmt = $conn->prepare("SELECT balance FROM accounts WHERE user_id = ? AND account_type = ?");
    $stmt->bind_param("is", $user_id, $account_type);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // لو ما فيه حساب من هذا النوع نضيفه برصيد 0
        $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_type, balance) VALUES (?, ?, 0)");
        $stmt->bind_param("is", $user_id, $account_type);
        $stmt->execute();
        return 0;
    }

    return (float)$result->fetch_assoc()['balance'];
}

$success_message = '';
$error_message   = '';

/* ================== معالجة الفورم ================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ---- صرف من الحساب المغلق ---- */
    if ($action === 'subtract') {
        $amount  = abs(floatval($_POST['amount'] ?? 0));
        $comment = $_POST['comment'] ?? '';

        if ($amount <= 0) {
            $error_message = "الرجاء إدخال مبلغ صحيح.";
        } else {
            $locked_balance = get_balance($conn, $user_id, 'مغلق');

            if ($amount > $locked_balance) {
                $error_message = "المبلغ أكبر من رصيد الحساب المغلق.";
            } else {
                // نقص من الحساب المغلق
                $locked_balance -= $amount;
                $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = 'مغلق'");
                $stmt->bind_param("di", $locked_balance, $user_id);
                $stmt->execute();
                $stmt->close();

                // نسجل العملية في transactions
                $stmt = $conn->prepare("INSERT INTO transactions (user_id, account_type, amount, comment) VALUES (?, 'مغلق', ?, ?)");
                $stmt->bind_param("ids", $user_id, $amount, $comment);
                $stmt->execute();
                $stmt->close();

                $success_message = "تم الصرف من الحساب المغلق بنجاح.";
                
            }
        }

    /* ---- إيداع في الحساب المغلق (مرة واحدة في الشهر) ---- */
    } elseif ($action === 'deposit_locked') {
        $deposit_amount = abs(floatval($_POST['deposit_amount'] ?? 0));

        if ($deposit_amount <= 0) {
            $error_message = "الرجاء إدخال مبلغ صحيح للإيداع.";
        } else {
            // نتأكد إن فيه رصيد إجمالي يكفي
            $total_balance = get_balance($conn, $user_id, 'إجمالي');
            if ($deposit_amount > $total_balance) {
                $error_message = "لا يوجد رصيد كافٍ في الرصيد الإجمالي.";
            } else {
                // نجيب آخر إيداع من الإعدادات (لازم يكون عندك عمود last_locked_deposit في جدول settings)
                $stmt = $conn->prepare("SELECT last_locked_deposit FROM settings WHERE user_id = ? LIMIT 1");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $res  = $stmt->get_result();
                $row  = $res->fetch_assoc();
                $stmt->close();

                $can_deposit_this_month = true;

                if ($row && !empty($row['last_locked_deposit'])) {
                    $last = new DateTime($row['last_locked_deposit']);
                    $now  = new DateTime();

                    // لو نفس الشهر والسنة → خلاص سوّى إيداع قبل
                    if ($last->format('Y-m') === $now->format('Y-m')) {
                        $can_deposit_this_month = false;
                    }
                }

                if (!$can_deposit_this_month) {
                    $error_message = "يمكنك الإيداع في الحساب المغلق مرة واحدة فقط في هذا الشهر.";
                } else {
                    // ننقص من الإجمالي
                    $new_total = $total_balance - $deposit_amount;
                    $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = 'إجمالي'");
                    $stmt->bind_param("di", $new_total, $user_id);
                    $stmt->execute();
                    $stmt->close();

                    // نزيد في الحساب المغلق
                    $locked_balance = get_balance($conn, $user_id, 'مغلق');
                    $locked_balance += $deposit_amount;
                    $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = 'مغلق'");
                    $stmt->bind_param("di", $locked_balance, $user_id);
                    $stmt->execute();
                    $stmt->close();

                    // نحدّث تاريخ آخر إيداع
                    $stmt = $conn->prepare("UPDATE settings SET last_locked_deposit = NOW() WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();

                   $success_message = "تم إضافة المبلغ إلى الحساب المغلق.";
                   
                }
            }
        }
    }
}

/* ================== حساب الشروط وعرض الصفحة ================== */

// 1) الراتب من السيشن
$salary = 0;
if (isset($_SESSION['monthly_salary'])) {
    $salary = (float)$_SESSION['monthly_salary'];
}

// 2) لو مو موجود → نجيب من settings
if ($salary <= 0) {
    $stmt = $conn->prepare("SELECT monthly_salary FROM settings WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();
    $stmt->close();

    if ($row && isset($row['monthly_salary'])) {
        $salary = (float)$row['monthly_salary'];
    }
}

// 3) الرصيد الإجمالي
$total_balance = get_balance($conn, $user_id, 'إجمالي');
// تحديث الراتب بناءً على الرصيد الإجمالي الجديد (في بداية الشهر)
if ($total_balance > 0) {
    $_SESSION['monthly_salary'] = $total_balance;

    $stmt = $conn->prepare("UPDATE settings SET monthly_salary = ? WHERE user_id = ?");
    $stmt->bind_param("di", $total_balance, $user_id);
    $stmt->execute();
    $stmt->close();
}

// 4) 30% من الراتب
$required_balance = ($salary > 0) ? $salary * 0.30 : 0;

// 5) رصيد الحساب المغلق
$locked_balance = get_balance($conn, $user_id, 'مغلق');

// 6) هل يقدر يدخل الحساب المغلق؟
if ($salary > 0 && $total_balance <= $required_balance) {
    $can_access_locked = true;
    $condition_message = "يمكنك الآن الدخول إلى الحساب المغلق.";
} else {
    $can_access_locked = false;
    $condition_message = "لا يمكنك الدخول إلى الحساب المغلق حتى يصل رصيدك إلى " . number_format($required_balance, 0) . " ريال أو أقل.";
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
            padding: 20px 20px 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .top-row h2 {
            margin: 0;
        }
        .small-deposit-form {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .small-deposit-form input[type="number"] {
            width: 80px;
            padding: 4px 6px;
            font-size: 13px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .small-deposit-form button {
            padding: 4px 10px;
            font-size: 13px;
            border-radius: 4px;
            border: none;
            background-color: #28a745;
            color: #fff;
            cursor: pointer;
        }
        .small-deposit-form button:hover {
            background-color: #218838;
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

    <a href="dashboard1.php" class="back-link">← الرجوع إلى الرصيد الإجمالي</a>

    <div class="container">

        <div class="top-row">
            <h2>حسابك المغلق 🛑</h2>

            <?php if (!$can_access_locked): ?>
                <!-- خانة إيداع صغيرة جنب العنوان، مرة واحدة في الشهر -->
                <form method="post" class="small-deposit-form">
                    <input type="number" name="deposit_amount" min="1" placeholder="إيداع" required>
                    <button type="submit" name="action" value="deposit_locked">إضافة</button>
                </form>
            <?php endif; ?>
        </div>

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

            <form method="post" action="">
                <div class="input-group">
                    <input type="number" name="amount" placeholder="المبلغ للصرف" required>
                    <input type="text" name="comment" placeholder="التعليق (مثل: سفر، طوارئ...)" required>
                </div>
                <button class="submit-btn" type="submit" name="action" value="subtract">صرف</button>
            </form>
        <?php endif; ?>

    </div>
</body>
</html>