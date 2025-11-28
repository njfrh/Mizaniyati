<?php
session_start(); 
require_once 'db.php'; 

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php?tab=login');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'مستخدم'; 

$error_message = '';
$notice = '';

// جلب بيانات المستخدم الأخرى من قاعدة البيانات
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

$user_email = $user_data['email'] ?? 'Not Found';

// معالجة تحديث الملف الشخصي
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $new_name     = trim($_POST['name'] ?? '');
    $new_email    = trim($_POST['email'] ?? '');
    $new_password = $_POST['password'] ?? '';

    // منطق التحقق والتحديث
    // (يجب أن تضع منطق تحديث قاعدة البيانات هنا)
    
    // مثال:
    // $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    // $stmt->bind_param("ssi", $new_name, $new_email, $user_id);
    // $stmt->execute();

    $notice = 'تم حفظ التغييرات بنجاح! ✅';
    $_SESSION['user_name'] = $new_name; // تحديث الجلسة
    $user_name = $new_name;
    $user_email = $new_email;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>الملف الشخصي - الإعدادات</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    /* ------------------ التنسيق الأساسي (الخلفية والحاوية) ------------------ */
    * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    
    body { 
        margin: 0; 
        /* الخلفية المتدرجة مثل auth.php */
        background: linear-gradient(135deg, #2AB7A9, #1E8E82 65%);
        display: flex; 
        justify-content: center;
        align-items: flex-start; /* نبدأ من الأعلى */
        min-height: 100vh;
        padding: 40px 20px; 
        direction: rtl; 
    }
    .container { 
        max-width: 500px; /* العرض الموحد */
        width: 100%;
        background: #fff; 
        border-radius: 18px; 
        padding: 30px; 
        /* ظل كبير وواضح مثل forgot_password.php */
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    h2 { text-align: center; color: #101826; margin-bottom: 30px; font-weight: 800; }
    
    /* زر الرجوع للخلف */
    .back-link { 
        display: inline-block; 
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
        transition: background 0.2s;
    }
    .back-link:hover { background: #0c5a53; }


    /* ------------------ تنسيق النماذج والحقول (مستوحى من auth.php) ------------------ */
    .profile-form { 
        display: flex; 
        flex-direction: column; 
        gap: 15px; 
    }
    .input-group { 
        position: relative; 
    }
    .profile-form label { 
        font-weight: 700; 
        color: #101826; 
        margin-bottom: 8px; 
        display: block; 
        font-size: 15px;
    }

    .profile-form input[type="text"], 
    .profile-form input[type="email"], 
    .profile-form input[type="password"] { 
        padding: 14px; /* حجم كبير مثل auth.php */
        border-radius: 10px; 
        border: 1px solid #dcdfe4; 
        width: 100%; 
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        font-size: 16px;
        transition: .25s;
    }
    .profile-form input:focus {
        border-color: #2AB7A9;
        box-shadow: 0 0 0 3px rgba(42,183,169,0.20);
        outline: none;
    }
    
    .btn-primary { 
        width: 100%;
        padding: 14px 20px; 
        background: #2AB7A9; /* اللون الأساسي من auth.php */
        color: white; 
        cursor: pointer; 
        border: none;
        border-radius: 12px; 
        font-weight: 800;
        transition: background 0.3s;
        margin-top: 15px;
    }
    .btn-primary:hover { background: #1E8E82; }

    /* تنبيهات النجاح والأخطاء */
    .notice {
        padding: 12px;
        background: #e6fff7;
        border: 1px solid #79ead2;
        color: #1E8E82;
        border-radius: 10px;
        font-size: 14px;
        margin-bottom: 20px;
        text-align: center;
    }
    .error {
        padding: 12px;
        background: #ffe8e8;
        border: 1px solid #ffb9b9;
        color: #b10000;
        border-radius: 10px;
        font-size: 14px;
        margin-bottom: 20px;
        text-align: center;
    }
</style>
</head>
<body>  

    <a href="dashboard1.php" class="back-link">← الرجوع الى لوحة التحكم</a>

    <div class="container">
        <h2>👤 إعدادات الحساب</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if (!empty($notice)): ?>
            <div class="notice"><?= htmlspecialchars($notice) ?></div>
        <?php endif; ?>

        <form method="post" class="profile-form">
            
            <div class="input-group">
                <label for="name">الاسم:</label>
                <input type="text" name="name" id="name" value="<?= htmlspecialchars($user_name) ?>" required>
            </div>

            <div class="input-group">
                <label for="email">البريد الإلكتروني:</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($user_email) ?>" required> 
            </div>

            


            <button type="submit" name="action" value="update_profile" class="btn-primary">حفظ التغييرات</button>
        </form>
    </div>
</body>
</html>