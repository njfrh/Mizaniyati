 <?php
session_start();
include "db.php"; // الاتصال بقاعدة البيانات

$user_id = $_SESSION['user_id'] ?? 1;

// دالة لجلب الرصيد الحالي
function get_balance($conn, $user_id, $account_type) {
    $stmt = $conn->prepare("SELECT balance FROM accounts WHERE user_id = ? AND account_type = ?");
    $stmt->bind_param("is", $user_id, $account_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // إذا لم يوجد حساب، سيتم إنشاءه برصيد 0
        $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_type, balance) VALUES (?, ?, 0)");
        $stmt->bind_param("is", $user_id, $account_type);
        $stmt->execute();
        return 0;
    }
    
    return (float)$result->fetch_assoc()['balance'];
}

$daily_balance = get_balance($conn, $user_id, 'مشتريات يومية');
$monthly_balance = get_balance($conn, $user_id, 'مشتريات شهرية');
$necessary_balance = get_balance($conn, $user_id, 'مشتريات ضرورية');

// معالجة المعاملات بعد إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $account_type = $_POST['account_type'] ?? '';
    $amount = abs(floatval($_POST['amount'] ?? 0));
    $comment = $_POST['comment'] ?? '';

    if ($action === 'add' && $amount > 0 && $account_type !== '') {
        if ($account_type === 'مشتريات يومية') {
            $daily_balance += $amount;
        } elseif ($account_type === 'مشتريات شهرية') {
            $monthly_balance += $amount;
        } elseif ($account_type === 'مشتريات ضرورية') {
            $necessary_balance += $amount;
        }

        // إدخال المعاملة في جدول المعاملات
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, account_type, amount, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $user_id, $account_type, $amount, $comment);
        $stmt->execute();
    } elseif ($action === 'subtract' && $amount > 0 && $account_type !== '') {
        if ($account_type === 'مشتريات يومية' && $daily_balance >= $amount) {
            $daily_balance -= $amount;
        } elseif ($account_type === 'مشتريات شهرية' && $monthly_balance >= $amount) {
            $monthly_balance -= $amount;
        } elseif ($account_type === 'مشتريات ضرورية' && $necessary_balance >= $amount) {
            $necessary_balance -= $amount;
        }

        // إدخال المعاملة في جدول المعاملات
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, account_type, amount, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $user_id, $account_type, -$amount, $comment);
        $stmt->execute();
    }

    // تحديث الأرصدة في قاعدة البيانات
    $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = 'مشتريات يومية'");
    $stmt->bind_param("di", $daily_balance, $user_id);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = 'مشتريات شهرية'");
    $stmt->bind_param("di", $monthly_balance, $user_id);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = 'مشتريات ضرورية'");
    $stmt->bind_param("di", $necessary_balance, $user_id);
    $stmt->execute();

    // إعادة توجيه إلى الصفحة نفسها لتحديث الأرصدة
    header("Location: accounts.php");
    exit;
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
      direction: rtl;
      background-color: #f8f8f8;
      margin: 0;
      padding: 0;
    }

    .container {
      width: 90%;
      max-width: 800px;
      margin: 50px auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    .account-box {
      background: #f0f0f0;
      margin: 15px 0;
      padding: 15px;
      border-radius: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    } .account-box h3 {
      font-size: 18px;
      margin: 0;
    }

    .account-box p {
      font-size: 16px;
      margin: 5px 0;
    }

    .input-group {
      margin-top: 20px;
    }

    input[type="number"], input[type="text"] {
      width: 100%;
      padding: 10px;
      margin: 5px 0;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 16px;
    }

    button {
      padding: 10px 20px;
      background-color: #4CAF50;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      width: 100%;
    }

    button:hover {
      background-color: #45a049;
    }
  </style>
</head>
<body>

  <div class="container">
    <h2>إدارة الحسابات</h2>

    <form method="post">
      <div class="account-box">
        <h3>مشتريات يومية</h3>
        <p>الرصيد: SAR <?= number_format($daily_balance, 0) ?></p>
      </div>
      <div class="input-group">
        <label>المبلغ</label>
        <input type="number" name="amount" placeholder="المبلغ" min="0" required>
        <label>التعليق</label>
        <input type="text" name="comment" placeholder="مثل: قهوة، مطعم..." required>
        <button type="submit" name="action" value="add">إضافة</button>
        <button type="submit" name="action" value="subtract">خصم</button>
        <input type="hidden" name="account_type" value="مشتريات يومية">
      </div>
    </form>

    <form method="post">
      <div class="account-box">
        <h3>مشتريات شهرية</h3>
        <p>الرصيد: SAR <?= number_format($monthly_balance, 0) ?></p>
      </div>
      <div class="input-group">
        <label>المبلغ</label>
        <input type="number" name="amount" placeholder="المبلغ" min="0" required>
        <label>التعليق</label>
        <input type="text" name="comment" placeholder="مثل: تسوق، فواتير..." required>
        <button type="submit" name="action" value="add">إضافة</button>
        <button type="submit" name="action" value="subtract">خصم</button>
        <input type="hidden" name="account_type" value="مشتريات شهرية">
      </div>
    </form>

    <form method="post">
      <div class="account-box">
        <h3>مشتريات ضرورية</h3>
        <p>الرصيد: SAR <?= number_format($necessary_balance, 0) ?></p>
      </div>
      <div class="input-group">
        <label>المبلغ</label>
        <input type="number" name="amount" placeholder="المبلغ" min="0" required>
        <label>التعليق</label>
        <input type="text" name="comment" placeholder="مثل: سفر، علاج..." required>
        <button type="submit" name="action" value="add">إضافة</button>
        <button type="submit" name="action" value="subtract">خصم</button>
        <input type="hidden" name="account_type" value="مشتريات ضرورية">
      </div>
    </form>
  </div>

</body>
</html>