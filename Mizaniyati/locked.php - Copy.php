 <?php
session_start();
include "db.php"; // الاتصال بقاعدة البيانات

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php?tab=login");
    exit();
}

$user_id = $_SESSION['user_id'];

// الحد الأدنى المطلوب للرصيد لفتح الحساب المغلق
$required_balance = 1000; // يمكنك تغيير هذه القيمة كما تشاء

// جلب الرصيد الإجمالي من الحسابات
$stmt = $conn->prepare("SELECT balance FROM accounts WHERE user_id = ? AND account_type = 'إجمالي'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $balance = $result->fetch_assoc()['balance'];

    // التحقق من إذا كان الرصيد كافي لدخول الحساب المغلق
    if ($balance >= $required_balance) {
        // عرض الحساب المغلق
        $stmt = $conn->prepare("SELECT balance FROM accounts WHERE user_id = ? AND account_type = 'مغلق'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $locked_balance = $result->num_rows > 0 ? $result->fetch_assoc()['balance'] : 0;

        // معالجة المعاملات بعد إرسال النموذج
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $amount = abs(floatval($_POST['amount'] ?? 0));
            $comment = $_POST['comment'] ?? '';

            // إضافة المبلغ إلى الحساب المغلق
            if ($action === 'add' && $amount > 0) {
                $locked_balance += $amount;
            }
            // خصم المبلغ من الحساب المغلق
            elseif ($action === 'subtract' && $amount > 0) {
                $locked_balance -= $amount;
            }

            // إدخال المعاملة في جدول المعاملات
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, account_type, amount, comment) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $user_id, 'مغلق', $amount, $comment);
            $stmt->execute();

            // تحديث الرصيد في قاعدة البيانات
            $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE user_id = ? AND account_type = 'مغلق'");
            $stmt->bind_param("di", $locked_balance, $user_id);
            $stmt->execute();

            // إعادة توجيه إلى نفس الصفحة لتحديث الأرصدة
            header("Location: locked.php");
            exit;
        }
    } else {
        echo "<h3>رصيدك غير كافٍ لفتح الحساب المغلق. تحتاج إلى " . ($required_balance - $balance) . " ريال لفتح الحساب المغلق.</h3>";
    }
}
?>

<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>الحساب المغلق</title>
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
      max-width: 700px;
      margin: 50px auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
      font-size: 22px;
    }

    .account-box {
      background: #f9f9f9;
      margin: 15px 0;
      padding: 15px;
      border-radius: 8px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 16px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .account-box h3 {
      font-size: 16px;
      margin: 0;
    }

    .account-box p {
      font-size: 14px;
      margin: 5px 0;
    }

    .input-group {
      margin-top: 20px;
      display: flex;
      justify-content: space-between;
      gap: 10px;
    }

    input[type="number"], input[type="text"] {
      width: 48%;
      padding: 10px;
      margin: 5px 0;
      border-radius: 8px;
      border: 1px solid #000; /* حدود سوداء فقط */
      font-size: 16px;
      transition: border-color 0.3s ease-in-out;
    }

    input[type="number"]:focus, input[type="text"]:focus {
      border-color: #4CAF50; /* عند التركيز يصبح الأخضر */
    }

    .input-group label {
      font-size: 14px;
      margin-bottom: 5px;
    }

    /* الحقول تكون شفافة بدون ألوان */
    . add-input, .spend-input {
      background-color: transparent;  /* خلفية شفافة */
      border: 1px solid #000;  /* حدود سوداء فقط */
    }

    button {
      padding: 10px 20px;
      background-color: transparent;
      color: #000;
      border: 1px solid #000;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      width: 100%;
      margin-top: 10px;
      transition: background-color 0.3s ease-in-out;
    }

    button:hover {
      background-color: #ddd; /* عند التمرير يصبح اللون خفيف */
    }

    .action-buttons {
      display: flex;
      justify-content: space-between;
    }

    .action-buttons button {
      width: 48%;
    }

    .balance-section {
      text-align: center;
      margin-bottom: 20px;
    }

    .balance-section h3 {
      font-size: 18px;
    }
  </style>
</head>
<body>

  <div class="container">
    <!-- الرصيد المغلق -->
    <div class="balance-section">
      <h3>الرصيد المغلق: SAR <?= number_format($locked_balance, 0) ?></h3>
    </div>

    <h2>إدارة الحساب المغلق</h2>

    <!-- إضافة أو صرف من الحساب المغلق -->
    <form method="post">
      <div class="account-box">
        <h3>حساب مغلق</h3>
      </div>
      <div class="input-group">
        <label>المبلغ</label>
        <input type="number" name="amount" placeholder="المبلغ" min="0" required class="add-input">
        <label>التعليق</label>
        <input type="text" name="comment" placeholder="مثل: علاج، سفر..." required class="spend-input">
      </div>
      <div class="action-buttons">
        <button type="submit" name="action" value="add">إضافة</button>
        <button type="submit" name="action" value="subtract">صرف</button>
        <input type="hidden" name="account_type" value="مغلق">
      </div>
    </form>
  </div>

</body>
</html>