<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إضافة مصروف</title>
<style>
body { font-family: Arial; padding: 20px; }
input, button { padding: 10px; margin: 5px; width: 250px; }
</style>
</head>
<body>  

<div class="container">
    <h2>💰 إضافة معاملة سريعة</h2>
    
    <form action="add_transaction.php" method="post" class="add-form">
        
        <label for="amount-input">المبلغ:</label>
        <input type="number" name="amount" id="amount-input" placeholder="SAR" min="0.01" step="0.01" required>

        <label for="comment-input">التعليق:</label>
        <input type="text" name="comment" id="comment-input" placeholder="مثل: قهوة من كوفي" required>
        
        <input type="hidden" name="action" value="subtract"> 
        
        <input type="hidden" name="section" value="يومية">

        <input type="hidden" name="category" value="أخرى">
        
        <button type="submit">إضافة المصروف</button>
    </form>
    
    <hr style="border: 0; border-top: 1px dashed #ccc; margin: 30px 0;">
    
    </div>

<style>
/* ------------------ تنسيق نموذج الإضافة (مطابق لطلبك) ------------------ */
.add-form { 
    display: flex; 
    flex-direction: column; /* جعل العناصر تظهر تحت بعضها */
    gap: 15px; /* تباعد بين العناصر */
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
.add-form input, .add-form select { 
    padding: 12px; 
    border-radius: 8px; 
    border: 1px solid #dcdfe4; 
    width: 100%; /* جعل حقول الإدخال بعرض كامل */
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.05); /* إضافة ظل بسيط للحقل */
}
.add-form button { 
    padding: 14px 20px; /* زيادة حجم الزر قليلاً */
    background: #00a87a; 
    color: white; 
    cursor: pointer; 
    border: none;
    border-radius: 8px;
    font-weight: bold;
    margin-top: 10px;
}
.add-form button:hover { background: #008a65; }
</style>


</body>
</html>