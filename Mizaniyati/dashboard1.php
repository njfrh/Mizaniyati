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
// ... بعد جلب $locked_balance

/* 🛑 ✅ الإضافات الجديدة: جلب وتحليل مصروفات الشهر الحالي */
$current_month = date('Y-m-01'); // بداية الشهر الحالي (مثل: 2025-11-01)

// 1. إجمالي المصروفات لهذا الشهر
// نجمع المبالغ السالبة فقط (المصروفات) التي حدثت هذا الشهر
$sql_total_spent = "SELECT SUM(amount) AS total_spent 
                    FROM transactions 
                    WHERE user_id = ? 
                    AND amount < 0 
                    AND created_at >= ?";
$stmt_total_spent = $conn->prepare($sql_total_spent);
$stmt_total_spent->bind_param("is", $user_id, $current_month);
$stmt_total_spent->execute();
$result_total_spent = $stmt_total_spent->get_result();
$total_spent_row = $result_total_spent->fetch_assoc();
// القيمة ستكون سالبة (مثل -500.00)، نأخذ القيمة المطلقة لعرضها
$monthly_expense_total = abs($total_spent_row['total_spent'] ?? 0);
$stmt_total_spent->close();


// 2. المصروفات حسب التصنيف (ضرورية، يومية، شهرية)
$sql_category_spent = "SELECT account_type, SUM(amount) AS category_spent 
                       FROM transactions 
                       WHERE user_id = ? 
                       AND amount < 0 
                       AND created_at >= ?
                       AND account_type IN ('ضرورية', 'يومية', 'شهرية')
                       GROUP BY account_type";
$stmt_category_spent = $conn->prepare($sql_category_spent);
$stmt_category_spent->bind_param("is", $user_id, $current_month);
$stmt_category_spent->execute();
$result_category_spent = $stmt_category_spent->get_result();

$category_expenses = [
    'ضرورية' => 0,
    'يومية' => 0,
    'شهرية' => 0,
    'الإجمالي' => $monthly_expense_total // لإيجاد النسبة المئوية
];

while ($row = $result_category_spent->fetch_assoc()) {
    // نستخدم القيمة المطلقة للمصروفات لسهولة العرض
    $category_expenses[$row['account_type']] = abs($row['category_spent']);
}
$stmt_category_spent->close();
/* 🛑 نهاية الإضافات الجديدة */

// ... بقية كود PHP أو HTML يبدأ من هنا
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
<body>
  <div class="user-menu-container">

    <div class="user-icon" onclick="toggleMenu()">

        <img src="user_icon.jpg" alt="User Profile" class="profile-image">
</div>
    <div class="dropdown-menu" id="userDropdown">
   <div class="menu-item header-name">مرحباً، <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'مستخدم'); ?></div>

    <a href="profile.php" class="menu-item">
        👤 المعلومات الشخصية
    </a>
    
    <a href="rate_app.php" class="menu-item">
         ⭐️ تقييم  ميزانيتي
    </a>
    
<a href="logout.php" class="menu-item exit">🚪 تسجيل الخروج</a>
    </a>
</div>
</div>

<div class="tabs">
    <div class="tab active">الرصيد</div> 
    
    <a href="reports.php" class="tab-link"> 
        <div class="tab">المعاملات  </div>
    </a>

     <a href="api_ai.php" class="tab-link"> 
        <div class="tab">المساعد الذكي</div>
    </a>
</div>

<style>
    /* إضافة هذا التنسيق في CSS لضمان عدم وجود تنسيق غريب للرابط */
    .tab-link {
        text-decoration: none; /* إزالة الخط تحت الرابط */
        color: inherit;      /* وراثة لون النص */
    }
</style>

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
     <!-- <div class="accounts"> -->
      <!-- حساب الترفيه يبقى كما هو دائماً -->
     <!-- <form method="post">
        <button type="submit" name="action" value="savings" class="account-card">
          <h3>حساب الترفيه</h3>
          
        </button>
      </form> -->

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
    margin-top: 50px;
  }
  .account-card {
    width: 180px;
    padding: 25px;
    border-radius: 10px;
    background-color: #dcd5d5ff;
    box-shadow: 0 0 5px rgba(68, 23, 23, 0.1);
    cursor: pointer;
    transition: 0.2s;
    text-align: center;
    margin-top: 29px;
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
  }<<<<<<< HEAD

 

/* تنسيق أيقونة المستخدم والقائمة المنسدلة */

.user-menu-container {

    position: relative;

    top: 20px;

    left: 20px;

    z-index: 1000;

}



.user-icon {

    width: 36px; /* **تم تصغير العرض هنا** */

    height: 36px; /* **تم تصغير الارتفاع هنا** */

    background-color: #f0f0f0;

    border-radius: 50%;

    display: flex;

    justify-content: center;

    align-items: center;

    cursor: pointer;

    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1); /* ظل أصغر */

    padding: 2px; /* تصغير الـ padding */

    overflow: hidden;

    transition: transform 0.2s;
    
    
   


}



.menu-item:hover {

    background-color: #f0f0f0;

}

/* هذا هو التعديل الأساسي لضمان عدم التظليل عند مرور الماوس */

.menu-item.header-name:hover {

    background-color: transparent; /* إلغاء لون الخلفية عند التمرير */

}



/* للتأكد من أن المؤشر لا يظهر كـ 'يد' */

.menu-item.header-name {

    cursor: default;

}

.profile-image {

    width: 100%;

    height: 100%;

    border-radius: 50%;

    object-fit: cover;

}



/* يجب تعديل مكان القائمة المنسدلة ليتناسب مع الحجم الجديد */

.dropdown-menu {

    position: absolute;

    top: 55px; /* تم تعديل المسافة من الأعلى: 36px (حجم الأيقونة) + 9px (مسافة) */

    right: 0;

    background: #fff;

    border-radius: 12px;

    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);

    width: 200px;

    overflow: hidden;

    padding: 10px 0;

    display: none;

    text-align: right;

    direction: rtl;

}

.dropdown-menu {

    position: absolute;

    top: 55px;

    left: 0; /* يبدأ من تحت الأيقونة مباشرة */

    background: #fff;

    border-radius: 12px;

    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);

    width: 200px;

    overflow: hidden;

    padding: 10px 0;

    display: none; /* يتم إخفاؤها مبدئياً */

    text-align: right;

    direction: rtl;

}



.dropdown-menu.show {

    display: block;

}



.menu-item {

    display: block;

    padding: 10px 15px;

    text-decoration: none;

    color: #333;

    font-size: 14px;

    transition: background-color 0.2s;

    cursor: pointer;

}



.menu-item:hover {

    background-color: #f0f0f0;

}



.menu-item.header-name {

    font-weight: bold;

    color: #101826;

    border-bottom: 1px solid #eee;

    margin-bottom: 5px;

    cursor: default;

}



.menu-item.logout {

    color: #dc3545; /* لون أحمر لتسجيل الخروج */
}
=======
  .account-card {
width: 250px !important;
height: 90px !important;
border-radius: 12px !important;
display: flex !important;
flex-direction: column;
justify-content: center;
align-items: center;
}

>>>>>>> f6ceebf7a42516279b1345742b8239e29172b07a
</style>
</head>

<body>

 
  <script>
    function toggleMenu() {
        // هذه الدالة سليمة وتظهر القائمة
        document.getElementById('userDropdown').classList.toggle('show');
    }

    // إغلاق القائمة عند الضغط خارجها
    window.onclick = function(event) {
        // التحقق مما إذا كان النقر لم يكن داخل حاوية القائمة المنسدلة بالكامل
        if (!event.target.closest('.user-menu-container')) {
            var dropdowns = document.getElementsByClassName("dropdown-menu");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }
</script>
</body>
</html>