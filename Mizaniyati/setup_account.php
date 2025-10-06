<?php
session_start();
include "db.php";

$user_id = $_SESSION['user_id'] ?? 1;

  if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['salary'], $_POST['auto_split']))  {
    // نجلب البيانات من النموذج
    $salary = isset($_POST["salary"]) ? (float) $_POST["salary"] : 0;
    $auto_split = isset($_POST["auto_split"]) ? (int) $_POST["auto_split"] : 0;
    $locked_amount = isset($_POST["locked_amount"]) ? (float) $_POST["locked_amount"] : 0;
    if ($locked_amount < 0) $locked_amount = 0;

    // حفظ البيانات في settings
    $sql = "INSERT INTO settings (user_id, monthly_salary, auto_split, closed_account_percent)
            VALUES ($user_id, $salary, $auto_split, $locked_amount)
            ON DUPLICATE KEY UPDATE 
                monthly_salary = $salary, 
                auto_split = $auto_split, 
                closed_account_percent = $locked_amount";
    $conn->query($sql);

    // حذف الحسابات القديمة
    $conn->query("DELETE FROM accounts WHERE user_id = '$user_id'");

    if ($auto_split === 1) {
        // تقسيم الراتب على حسابين
        $open_amount = $salary - $locked_amount;
        $conn->query("INSERT INTO accounts (user_id, account_type, balance)
                      VALUES ('$user_id', 'مفتوح', '$open_amount')");
        $conn->query("INSERT INTO accounts (user_id, account_type, balance)
                      VALUES ('$user_id', 'مغلق', '$locked_amount')");
    } else {
        // حفظ الراتب في حساب إجمالي واحد
        $conn->query("INSERT INTO accounts (user_id, account_type, balance)
                      VALUES ('$user_id', 'إجمالي', '$salary')");
    }

    // بعد ما المستخدم يضغط "التالي" فعلاً، ننتقل للصفحة الرئيسية
    header("Location: dashboard1.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>ميزانيتي - إعداد الحساب</title>
  <style>
    body {
      font-family: 'Tahoma', sans-serif;
      direction: rtl;
      background-color: #f8f8f8;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
    }

    .container {
      background: #fff;
      width: 90%;
      max-width: 400px;
      padding: 40px 30px;
      border-radius: 20px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      text-align: center;
    }

    h2 {
      font-size: 28px;
      margin-bottom: 5px;
    }

    h3 {
      font-size: 18px;
      margin-top: 0;
      margin-bottom: 30px;
      color: #555;
    }

    label {
      font-size: 16px;
      display: block;
      margin-bottom: 8px;
    }

    input[type="number"] {
      width: 100%;
      padding: 12px;
      border: 2px solid #ccc;
      border-radius: 12px;
      font-size: 16px;
      text-align: center;
      outline: none;
      transition: border-color 0.2s;
    }

    input[type="number"]:focus {
      border-color: #4CAF50;
    }

    p {
      font-size: 16px;
      margin: 25px 0 10px;
    }

    .radio-group {
      display: flex;
      justify-content: space-around;
      margin-bottom: 20px;
    }

    .radio-option {
      display: flex;
      align-items: center;
      gap: 8px;
      background-color: #f0f0f0;
      padding: 10px 20px;
      border-radius: 25px;
      cursor: pointer;
      transition: background 0.2s;
      font-size: 16px;
    }

    .radio-option input {
      display: none;
    }

    .radio-option:hover {
      background-color: #e0e0e0;
    }

    .radio-option input:checked + span {
      font-weight: bold;
      color: #2e7d32;
    }

    #lockedField {
      margin-top: 20px;
      display: none;
    }

    button {
      width: 100%;
      padding: 14px;
      font-size: 18px;
      background-color: #ccc;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      transition: background 0.2s;
      margin-top: 25px;
    }

    button:hover {
      background-color: #aaa;
    }
  </style>
  <script>
    function toggleLockedField(value) {
      const lockedDiv = document.getElementById("lockedField");
      lockedDiv.style.display = (value === "1") ? "block" : "none";
    }
  </script>
</head>
<body>

  <div class="container">
    <h2>ميزانيتي</h2>
    <h3>إعداد حساب المالي</h3>

    <form method="POST" action="setup_account.php">
      <label>إدخال الراتب الشهري</label>
      <input type="number" name="salary" placeholder="0" min="0" required>

      <p>هل ترغب بتقسيم راتبك تلقائيًا على حسابين؟</p>

      <div class="radio-group">
        <label class="radio-option">
          <input type="radio" name="auto_split" value="1" onclick="toggleLockedField(this.value)" required>
          <span>نعم</span>
        </label>

        <label class="radio-option">
          <input type="radio" name="auto_split" value="0" onclick="toggleLockedField(this.value)">
          <span>لا</span>
        </label>
      </div>

      <div id="lockedField">
        <label>تحديد المبلغ من راتبك للحساب المغلق:</label>
        <input type="number" name="locked_amount" placeholder="0" min="0">
      </div>

      <button type="submit">التالي</button>
    </form>
  </div>

</body>
</html>