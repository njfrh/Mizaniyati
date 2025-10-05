<?php
session_start();
include "db.php";

$user_id = $_SESSION['user_id'] ?? 1;

// تأكيد وجود الحساب الإجمالي
$check = $conn->query("SELECT balance FROM accounts WHERE user_id = $user_id AND account_type = 'إجمالي'");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO accounts (user_id, account_type, balance) VALUES ($user_id, 'إجمالي', 0)");

     $check = $conn->query("SELECT balance FROM accounts WHERE user_id = $user_id AND account_type = 'إجمالي'");

}

// جلب الرصيد الحالي
$row = $check->fetch_assoc() ?? ['balance' => 0];
$total_balance = (float)$row['balance'];

// معالجة الأزرار
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $amount = abs(floatval($_POST['amount'] ?? 0)); // الرقم اللي يكتبه المستخدم
    if ($action === 'add' && $amount > 0) {
        $total_balance += $amount;
    } elseif ($action === 'subtract' && $amount > 0) {
        $total_balance = max(0, $total_balance - $amount);
    }
    $conn->query("UPDATE accounts SET balance = $total_balance WHERE user_id = $user_id AND account_type = 'إجمالي'");
    header("Location: dashboard1.php");
    exit;
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>ميزانيتي</title>
<style>
  body {
    margin: 0;
    font-family: "Tahoma", sans-serif;
    background-color: #fff;
    color: #111;
    text-align: center;
    direction: rtl;
  }

  .topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 18px;
    background-color: #fff;
  }
  .topbar h1 {
    margin: 0;
    font-size: 18px;
    font-weight: bold;
  }

  .tabs {
    display: flex;
    justify-content: center;
    gap: 8px;
    padding: 10px 0;
  }
  .tab {
    background: #eee;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    color: #666;
  }
  .tab.active {
    background: #888;
    color: #fff;
    font-weight: bold;
  }

  .content {
    padding: 40px 15px 60px;
  }
  .title {
    color: #777;
    font-size: 15px;
    margin-bottom: 8px;
  }
  .balance {
    font-size: 36px;
    font-weight: bold;
    margin-bottom: 50px;
  }

  /* الدوائر والخانات */
  .stats {
    display: flex;
    justify-content: center;
    gap: 50px;
    align-items: center;
  }

  .circle {
    width: 90px;
    height: 90px;
    background: #eee;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: inset 0 0 3px rgba(0,0,0,0.1);
  }
  .circle button {
    background: none;
    border: none;
    font-size: 26px;
    font-weight: bold;
    color: #444;
    cursor: pointer;
  }
  .circle button:hover {
    color: #000;
  }
  .circle label {
    font-size: 13px;
    color: #555;
    margin-top: 4px;
  }

  input.amount-box {
    width: 60px;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 8px;
    text-align: center;
    font-size: 14px;
    margin-right: 6px;
  }

  .form-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }
</style>
</head>
<body>

  <div class="topbar">
    <div>🔔</div>
    <h1>ميزانيتي</h1>
    <div>☰</div>
  </div>

  <div class="tabs">
    <div class="tab active">الرصيد</div>
    <div class="tab">التقارير</div>
    <div class="tab">الحسابات</div>
  </div>

  <div class="content">
    <div class="title">الرصيد الإجمالي</div>
    <div class="balance">SAR <?= number_format($total_balance, 0) ?></div>

    <div class="stats">
      <form method="post" class="form-row">
        <input type="number" name="amount" class="amount-box" placeholder="مبلغ" min="0">
        <div class="circle">
          <button type="submit" name="action" value="add">+</button>
          <label>إضافة</label>
        </div>
      </form>

      <form method="post" class="form-row">
        <input type="number" name="amount" class="amount-box" placeholder="مبلغ" min="0">
        <div class="circle">
          <button type="submit" name="action" value="subtract">−</button>
          <label>تقليل</label>
        </div>
      </form>
    </div>
  </div>

</body>
</html>