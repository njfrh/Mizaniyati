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

/* ✅ إضافة بسيطة: نتأكد هل عنده حساب مغلق ولا لا */
$locked_result = $conn->query("SELECT balance FROM accounts WHERE user_id = $user_id AND account_type = 'مغلق'");
$has_locked_account = ($locked_result && $locked_result->num_rows > 0);
$locked_balance = 0;
if ($has_locked_account) {
    $locked_row    = $locked_result->fetch_assoc();
    $locked_balance = (float)$locked_row['balance'];
}
// لو اختار "لا" في الإعداد → ما ينشأ حساب مغلق أصلاً → $has_locked_account = false

// معالجة الأزرار
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $amount = abs(floatval($_POST['amount'] ?? 0));

    if ($action === 'add' && $amount > 0) {
        $total_balance += $amount;

    } elseif ($action === 'subtract' && $amount > 0) {
        $total_balance = max(0, $total_balance - $amount);

    } elseif ($action === 'savings') {
        header("Location: savings.php");
        exit;

    } elseif ($action === 'locked') {
        header("Location: locked.php");
        exit;
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
    justify-content: center;
    padding: 15px 18px;
    background-color: #fff;
    text-align: center;
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

  /* البوكسات */
  .accounts {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-top: 40px;
  }
  .account-card {
    width: 150px;
    padding: 15px;
    border-radius: 10px;
    background-color: #fff;
    box-shadow: 0 0 5px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: 0.2s;
    text-align: center;
  }
  .account-card:hover {
    background-color: #f5f5f5;
    transform: scale(1.03);
  }
  .account-card h3 {
    margin: 0;
    font-size: 14px;
    color: #222;
  }
  .account-card p {
    margin: 6px 0;
    font-weight: bold;
    font-size: 18px;
  }
  .account-card small {
    color: #777;
    font-size: 12px;
  }

  button.account-card {
    background: none;
    border: none;
  }
  .account-card {
width: 250px !important;
height: 90px !important;
border-radius: 12px !important;
display: flex !important;
flex-direction: column;
justify-content: center;
align-items: center;
}

</style>
</head>
<body>

  <div class="topbar">
    
    <h1>ميزانيتي</h1>
    
  </div>

  <div class="tabs">
    <div class="tab active">الرصيد</div>
    <div class="tab">التقارير</div>
    
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

    <!-- ✅ الحسابات -->
    <div class="accounts">
      <!-- حساب الترفيه يبقى كما هو دائماً -->
      <form method="post">
        <button type="submit" name="action" value="savings" class="account-card">
          <h3>حساب الترفيه</h3>
          
        </button>
      </form>

      <!-- ✅ حساب مغلق: يظهر فقط إذا فيه سجل في جدول accounts (يعني المستخدم اختار نعم) -->
      <?php if ($has_locked_account): ?>
      <form method="post">
        <button type="submit" name="action" value="locked" class="account-card">
          <h3>حساب مغلق</h3>
         
        </button>
      </form>
      <?php endif; ?>
    </div>

  </div>
</body>
</html>