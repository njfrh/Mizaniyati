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
    * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { background:#f4f5f7; margin:0; padding:20px; direction:rtl; }
    .container { max-width: 900px; margin: 30px auto; background:#fff; border-radius:14px; padding:30px; box-shadow:0 8px 24px rgba(0,0,0,.08); }
    h2 { text-align:center; color:#101826; margin-bottom:30px; }
    .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #101826; font-weight: 600; }

    /* ------------------ تنسيق نموذج الإضافة ------------------ */
    .add-form { 
        display: flex; 
        flex-direction: column; 
        gap: 15px; 
        margin-bottom: 30px; 
        padding: 20px; 
        border: 1px solid #ddd; 
        border-radius: 12px;
        background-color: #f9f9f9;
    }
    .add-form label {
        font-weight: 600;
        color: #101826;
        margin-top: 5px;
    }
    .add-form input[type="number"], 
    .add-form input[type="text"], 
    .add-form select { 
        padding: 12px; 
        border-radius: 8px; 
        border: 1px solid #dcdfe4; 
        width: 100%; 
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
    }
    .add-form button { 
        padding: 14px 20px; 
        background: #00a87a; 
        color: white; 
        cursor: pointer; 
        border: none;
        border-radius: 8px;
        font-weight: bold;
        margin-top: 10px;
    }
    .add-form button:hover { background: #008a65; }
    
    /* ------------------ تنسيق سجل العمليات (الـ Boxes) ------------------ */
    .transactions-list {
        display: flex;
        flex-direction: column;
        gap: 10px; 
    }
    .transaction-box {
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #fff;
        border-right: 5px solid; 
    }
    .transaction-box.income { border-right-color: #0b7a3b; } /* لون أخضر للإيراد */
    .transaction-box.expense { border-right-color: #dc3545; } /* لون أحمر للمصروف */
    
    .comment-text {
        font-size: 16px;
        font-weight: 600;
        color: #101826;
        flex-grow: 1;
    }
    .details {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 13px;
        color: #777;
    }
    .amount-value {
        font-weight: bold;
    }

    /* 🛑 تنسيق أزرار الإجراءات (تعديل وحذف) */
    .actions {
        display: flex;
        gap: 8px;
        margin-right: 15px;
        flex-shrink: 0; 
    }
    .action-btn {
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        border: 1px solid transparent;
        transition: all 0.2s;
    }
    .edit-btn {
        background-color: #f7f7f7;
        color: #333;
        border-color: #ddd;
    }
    .edit-btn:hover {
        background-color: #eee;
    }
    .delete-btn {
        background-color: #dc3545;
        color: white;
        border: none;
    }
    .delete-btn:hover {
        background-color: #c82333;
    }

</style>
</head>
<body>  
    <a href="dashboard1.php" class="back-link">← الرجوع إلى لوحة التحكم</a>

    <div class="container">
        
        <h2>💰 إضافة معاملة سريعة</h2>
        
        <form action="add_transaction.php" method="post" class="add-form">
            
            <label for="amount-input">المبلغ:</label>
            <input type="number" name="amount" id="amount-input" placeholder="SAR" min="0.01" step="0.01" required>

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
            
            <button type="submit">إضافة المصروف</button>
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
                    $sign = $is_income ? '+' : '-';
                ?>
                <div class="transaction-box <?= $is_income ? 'income' : 'expense' ?>">
                    
                    <div class="comment-text">
                        <?= htmlspecialchars($t['comment']) ?>
                    </div>
                    
                    <div class="details">
                        <span class="amount-value" style="color: <?= $is_income ? '#0b7a3b' : '#dc3545' ?>;">
                            <?= $sign . $display_amount ?> SAR
                        </span>
                        
                        <span class="category-tag">
                            <?= htmlspecialchars($t['account_type'] ?? 'غير محدد') ?>
                        </span>
                        
                        <span class="date-time">
                            <?= date('Y-m-d H:i', strtotime($t['created_at'])) ?>
                        </span>

                        <div class="actions">
                            <a href="edit_transaction.php?id=<?= htmlspecialchars($t['id']) ?>" class="action-btn edit-btn">تعديل</a>
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
            // إنشاء نموذج (Form) ديناميكياً لإرسال طلب POST
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