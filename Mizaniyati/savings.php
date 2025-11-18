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
        $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_type, balance) VALUES (?, ?, 0)");
        $stmt->bind_param("is", $user_id, $account_type);
        $stmt->execute();
        return 0;
    }
    
    return (float)$result->fetch_assoc()['balance'];
}

// رصيد إجمالي
$total_balance = get_balance($conn, $user_id, 'إجمالي');

// متغير لتخزين رسالة الخطأ (إن وجدت)
$error = "";

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $amount  = abs(floatval($_POST['amount']));
    $comment = $_POST['comment'] ?? '';
    $account_type = $_POST['account_type'] ?? '';

    // تحقق من المبلغ
    if ($amount <= 0) {
        $error = "الرجاء إدخال مبلغ صحيح أكبر من صفر.";
    }
    // تحقق من كفاية الرصيد الإجمالي
    elseif ($amount > $total_balance) {
        $error = "المبلغ أكبر من الرصيد الإجمالي.";
    }
    else {
        // خصم من الرصيد الإجمالي
        $total_balance -= $amount;

        // تخزين العملية
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, account_type, amount, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $user_id, $account_type, $amount, $comment);
        $stmt->execute();
        $stmt->close();

        // تحديث الرصيد الإجمالي
        $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = 'إجمالي'");
        $stmt->bind_param("di", $total_balance, $user_id);
        $stmt->execute();
        $stmt->close();

        header("Location: savings.php");
        exit;
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إدارة الحسابات</title>
<style>
body {
  font-family: 'Tahoma', sans-serif;
  background-color: #f8f8f8;
  margin: 0; padding: 0;
}
.container {
  width: 90%; max-width: 700px;
  margin: 50px auto;
  background: #fff;
  padding: 30px; border-radius: 10px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
h2 { text-align:center; margin:20px 0; font-size:22px; }
.account-box {
  background:#f9f9f9; padding:15px; 
  margin:15px 0; border-radius:8px;
  display:flex; justify-content:space-between;
  box-shadow:0 2px 5px rgba(0,0,0,0.1);
}
.input-group { display:flex; gap:10px; margin-top:20px; }
input { width:48%; padding:10px; border-radius:8px; border:1px solid #000; }
button {
  padding:10px; width:100%;
  background:transparent; border:1px solid #000;
  border-radius:8px; margin-top:10px;
  cursor:pointer; transition:0.3s;
}
button:hover { background:#ddd; }
.action-buttons { display:flex; gap:10px; }
.balance-section { text-align:center; margin-bottom:20px; }

.back-link {
  display:block;
  text-align:center;
  margin-top:20px;
  font-size:15px;
  text-decoration:none;
  color:#007bff;
}
.back-link:hover {
  text-decoration:underline;
}
.error-box {
  background:#ffdede;
  color:#900;
  padding:10px;
  border-radius:8px;
  text-align:center;
  margin-bottom:15px;
  font-size:14px;
}
</style>
</head>
<body>

<a href="dashboard1.php" class="back-link">← الرجوع إلى صفحة الرصيد الإجمالي</a>

<div class="container">

  <div class="balance-section">
    <h3>الرصيد الإجمالي: SAR <?= number_format($total_balance, 0) ?></h3>
  </div>

  <h2>إدارة الحسابات</h2>

  <?php if ($error): ?>
    <div class="error-box">
      <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
    </div>
  <?php endif; ?>

  <!-- مشتريات يومية -->
  <form method="post">
    <div class="account-box"><h3>مشتريات يومية</h3></div>
    <div class="input-group">
      <input type="number" name="amount" placeholder="المبلغ" required min="1">
      <input type="text" name="comment" placeholder="تعليق" required>
    </div>
    <div class="action-buttons">
      <button type="submit" name="action" value="subtract">صرف</button>
      <input type="hidden" name="account_type" value="مشتريات يومية">
    </div>
  </form>

  <!-- مشتريات شهرية -->
  <form method="post">
    <div class="account-box"><h3>مشتريات شهرية</h3></div>
    <div class="input-group">
      <input type="number" name="amount" placeholder="المبلغ" required min="1">
      <input type="text" name="comment" placeholder="تعليق" required>
    </div>
    <div class="action-buttons">
      <button type="submit" name="action" value="subtract">صرف</button>
      <input type="hidden" name="account_type" value="مشتريات شهرية">
    </div>
  </form>

  <!-- مشتريات ضرورية -->
  <form method="post">
    <div class="account-box"><h3>مشتريات ضرورية</h3></div>
    <div class="input-group">
      <input type="number" name="amount" placeholder="المبلغ" required min="1">
      <input type="text" name="comment" placeholder="تعليق" required>
    </div>
    <div class="action-buttons">
      <button type="submit" name="action" value="subtract">صرف</button>
      <input type="hidden" name="account_type" value="مشتريات ضرورية">
    </div>
  </form>

</div>

</body>
</html>