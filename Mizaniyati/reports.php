<?php
session_start(); 
require_once 'db.php'; // الاتصال بقاعدة البيانات

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php?tab=login');
    exit;
}

$user_id = $_SESSION['user_id'];
$transactions = [];

// ✅ جلب / إنشاء حساب الرصيد الإجمالي
$check = $conn->query("SELECT balance FROM accounts WHERE user_id = $user_id AND account_type = 'إجمالي'");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO accounts (user_id, account_type, balance) VALUES ($user_id, 'إجمالي', 0)");
    $check = $conn->query("SELECT balance FROM accounts WHERE user_id = $user_id AND account_type = 'إجمالي'");
}

$row = $check->fetch_assoc() ?? ['balance' => 0];
$total_balance = (float)$row['balance'];


// 🛑 جلب ID، المبلغ، التعليق، نوع الحساب (الذي هو الآن نوع المشتريات)، وتاريخ الإنشاء
$sql = "SELECT id, amount, comment, account_type, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id); 
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>صفحة التقارير وسجل العمليات</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    /* ------------------ التنسيق الأساسي (الخلفية والحاوية) ------------------ */
    * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    
    body { 
        margin: 0; 
        background: linear-gradient(135deg, #2AB7A9, #1E8E82 65%);
        display: flex; /* لمركزة الحاوية رأسياً وأفقياً */
        justify-content: center;
        align-items: flex-start; /* نبدأ من الأعلى */
        min-height: 100vh;
        padding: 40px 20px; 
        direction: rtl; 
    }
    .container { 
        max-width: 500px; /* ⬅️ تم تصغير العرض لـ 500px بناءً على طلبك */
        width: 100%; /* للتأكد من أنها تستغل العرض في الجوال */
        background: #fff; 
        border-radius: 18px; 
        padding: 30px; 
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    h2 { text-align: center; color: #101826; margin-bottom: 30px; font-weight: 700; }
    
    /* زر الرجوع للخلف */
    .back-link { 
        display: inline-block; 
        margin-bottom: 25px; 
        text-decoration: none; 
        color: #fff; 
        font-weight: 600; 
        padding: 8px 15px; 
        background: #116B63; 
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        position: fixed; /* تثبيت الزر في الزاوية */
        top: 20px;
        right: 20px;
        z-index: 100;
    }
    .back-link:hover { background: #0c5a53; }


  /* ------------------ تنسيق نموذج الإضافة ------------------ */
  .add-form { 
        display: flex; /* تحويل إلى عمود واحد عمودي */
        flex-direction: column;
        gap: 15px; 
        margin-bottom: 40px; 
        padding: 25px; 
        border: 1px solid #ddd; 
        border-radius: 12px;
        background-color: #f9f9f9;
    }
    
    .add-form label { font-weight: 600; color: #101826; margin-bottom: 5px; display: block; }
    .input-group { display: flex; flex-direction: column; } 

    .add-form input[type="number"], 
    .add-form input[type="text"], 
    .add-form select { 
        padding: 14px; /* حجم حقول auth.php */
        border-radius: 10px; 
        border: 1px solid #dcdfe4; 
        width: 100%; 
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        transition: border-color 0.2s;
    }
    .add-form input:focus, .add-form select:focus {
        border-color: #2AB7A9; /* لون التركيز الأخضر */
        box-shadow: 0 0 0 3px rgba(42,183,169,0.20);
        outline: none;
    }

    .btn-primary { 
        padding: 14px 20px; 
        background: #2AB7A9; 
        color: white; 
        cursor: pointer; 
        border: none;
        border-radius: 10px; 
        font-weight: bold;
        transition: background 0.3s;
        width: 100%; /* يجب أن يكون الزر بعرض كامل */
    }
    .btn-primary:hover { background: #1E8E82; }
    

    /* ------------------ تنسيق سجل العمليات (مُعدَّل للعرض الضيق) ------------------ */
    .transactions-list {
        display: flex;
        flex-direction: column;
        gap: 10px; 
    }
    .transaction-box {
        padding: 15px 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
        display: flex;
        flex-wrap: wrap; /* السماح للعناصر بالنزول لسطر جديد */
        align-items: flex-start;
        background-color: #ffffff;
        border-right: 6px solid; 
        transition: transform 0.2s;
    }
    .transaction-box.income { border-right-color: #0b7a3b; } 
    .transaction-box.expense { border-right-color: #dc3545; } 
    
    .comment-text {
        font-size: 17px;
        font-weight: 600;
        color: #101826;
        flex-grow: 1;
        width: 100%; /* التعليق يأخذ عرض كامل وينزل سطر */
        margin-bottom: 8px;
    }
    .details {
        display: flex;
        flex-wrap: wrap; 
        align-items: center;
        gap: 8px 15px; /* مسافة بين العناصر */
        font-size: 14px;
        color: #777;
        width: 100%; /* التفاصيل تأخذ عرض كامل */
    }
    .amount-value {
        font-weight: bold;
        text-align: right;
    }
    .category-tag {
        background-color: #f0f0f0;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        color: #555;
    }

    /* تنسيق أزرار الإجراءات */
   /* ------------------ تنسيق أزرار الإجراءات (تعديل وحذف) ------------------ */
.actions {
    display: flex;
    gap: 5px; /* مسافة أصغر بين الأزرار */
    margin-right: auto; /* دفع الأزرار إلى أقصى اليسار */
    flex-shrink: 0; 
    align-items: center;
}

.action-btn {
    /* أساسيات الزر */
    padding: 6px 10px; 
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s, color 0.2s;
    text-decoration: none; /* مهم للروابط <a> */
    display: flex;
    align-items: center;
    gap: 4px;
}

/* زر التعديل */
.edit-btn {
    background-color: #f0f8ff; /* خلفية فاتحة جداً */
    color: #007bff; /* لون أزرق */
    border: 1px solid #cce5ff;
}
.edit-btn:hover {
    background-color: #e3f2ff;
    border-color: #a6caff;
}
/* إضافة الأيقونة كجزء من النص */
.edit-btn:before {
    content: "✍️"; 
    font-size: 12px;
}

/* زر الحذف */
.delete-btn {
    background-color: #fff0f0; /* خلفية فاتحة جداً */
    color: #dc3545; /* لون أحمر */
    border: 1px solid #f5c6cb;
}
.delete-btn:hover {
    background-color: #f8d7da;
    border-color: #f1aeb5;
}
/* إضافة الأيقونة كجزء من النص */
.delete-btn:before {
    content: "🗑️";
    font-size: 12px;
}
</style>
</head>
<body>  
    <a href="dashboard1.php" class="back-link">← الرجوع إلى لوحة التحكم</a>

    <div class="container">
        
        <h2>الرصيد الإجمالي: <?php echo number_format($total_balance); ?> SAR 🪙</h2>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="error-msg">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <form action="add_transaction.php" method="post" class="add-form">
            
            <label for="amount-input">المبلغ:</label>
            <input type="number" name="amount" id="amount-input" value="..." min="1" step="1" required>

            <label for="comment-input">التعليق:</label>
            <input type="text" name="comment" id="comment-input" placeholder="مثل: قهوة من كوفي" required>
            
            <label for="account-select">تصنيف المشتريات:</label>
            <select name="account_type" id="account-select" required>
                <option value="ضرورية">المشتريات الضرورية</option>
                <option value="يومية">المشتريات اليومية</option>
                <option value="شهرية">المشتريات الشهرية</option>
            </select>
            
            <input type="hidden" name="action" value="subtract"> 
            <input type="hidden" name="section" value="يومية">
            <input type="hidden" name="category" value="أخرى">
            
            <button type="submit" class= btn-primary >إضافة المصروف</button>
        </form> 

<hr style="border: 0; border-top: 1px dashed #ccc; margin: 30px 0;">


        <h2>🧾 سجل العمليات</h2>
        
        <?php if (empty($transactions)): ?>
            <p style="text-align: center; color: #777;">لا توجد معاملات مسجلة بعد.</p>
        <?php else: ?>
            <div class="transactions-list">
                <?php foreach ($transactions as $t): ?>
                <?php 
                    $is_income = $t['amount'] > 0;
                    $display_amount = number_format(abs($t['amount']), 2); 
                    $sign = $is_income ? '-' : '-';
                ?>
                <div class="transaction-box <?= $is_income ? 'income' : 'expense' ?>">
                    
                    <div class="comment-text">
                        <?= htmlspecialchars($t['comment']) ?>
                    </div>
                    
                    <div class="details">
                        <span class="amount-value" style="color: <?= $is_income ? '#ff0303ff' : '#ff0019ff' ?>;">
                            <?= $sign . $display_amount ?> SAR
                        </span>
                        
                        <span class="category-tag">
                            <?= htmlspecialchars($t['account_type'] ?? 'غير محدد') ?>
                        </span>
                        
                        <span class="date-time">
                            <?= date('Y-m-d H:i', strtotime($t['created_at'])) ?>
                        </span>

                        <div class="actions">
    <?php if ($t['account_type'] !== 'مغلق'): ?>
        <a href="edit_transaction.php?id=<?= htmlspecialchars($t['id']) ?>" class="action-btn edit-btn">تعديل</a>
    <?php endif; ?>

    <button onclick="confirmDelete(<?= htmlspecialchars($t['id']) ?>)" class="action-btn delete-btn">حذف</button>
</div>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function confirmDelete(id) {
        if (confirm("هل أنت متأكد من حذف هذه العملية؟ سيتم تعديل رصيد حسابك.")) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'delete_transaction.php';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'id';
            input.value = id;

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>