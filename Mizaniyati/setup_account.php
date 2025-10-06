<?php
session_start();
include "db.php";
// 12222
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
            font-family: Tahoma;
            text-align: center;
            direction: rtl;
        }
        input, button {
            margin: 10px;
            padding: 8px;
        }
        #lockedField {
            display: none;
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

    <h2>ميزانيتي</h2>
    <h3>إعداد حسابك المالي</h3>

    <form method="POST" action="setup_account.php">
        <label>إدخال الراتب الشهري</label><br>
        <input type="number" name="salary" placeholder="إدخال الراتب الشهري" min="0" required><br>

        <p>هل ترغب بتقسيم راتبك تلقائيًا على حسابين؟</p>
        <label><input type="radio" name="auto_split" value="1" onclick="toggleLockedField(this.value)" required> نعم</label>
        <label><input type="radio" name="auto_split" value="0" onclick="toggleLockedField(this.value)"> لا</label><br>

        <div id="lockedField">
            <label>تحديد المبلغ من راتبك للحساب المغلق:</label><br>
            <input type="number" name="locked_amount" placeholder="المبلغ للحساب المغلق" min="0"><br>
        </div>

        <button type="submit">التالي</button>
    </form>

</body>
</html>