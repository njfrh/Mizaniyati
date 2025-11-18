<?php
session_start();
require_once 'db.php'; // الاتصال بقاعدة البيانات


$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

function fetch_user_data($conn, $user_id) {
    // 1. نجلب البيانات من جدول users (الاسم، الإيميل)
    $stmt = $conn->prepare('SELECT name, email, password_hash FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    

    
    // دمج البيانات
    if ($user) {
        // نضمن إعادة قيم افتراضية إذا لم يكن هناك ملف شخصي في جدول user_profile
        return array_merge($user, $profile ?: ['salary' => 0, 'occupation' => '']);
    }
    return false;
}

$user_data = fetch_user_data($conn, $user_id);

/* ================== معالجة التعديل ================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info') {
        $new_name = trim($_POST['new_name'] ?? '');
        $new_email = trim($_POST['new_email'] ?? '');

        if (empty($new_name) || empty($new_email)) {
            $errors[] = 'الاسم والإيميل مطلوبان.';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'صيغة البريد الإلكتروني غير صالحة.';
        } else {
            // التحقق من أن الإيميل غير مستخدم من قبل مستخدم آخر
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$new_email, $user_id]);
            if ($stmt->fetch()) {
                $errors[] = 'هذا البريد الإلكتروني مستخدم بالفعل من قبل حساب آخر.';
            } else {
                // تحديث المعلومات
                $stmt = $conn->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
                $stmt->execute([$new_name, $new_email, $user_id]);
                
                // تحديث الجلسة والصفحة
                $_SESSION['user_name'] = $new_name;
                $success = 'تم تحديث المعلومات بنجاح!';
                $current_name = $new_name;
                $current_email = $new_email;
            }
        }
    } elseif ($action === 'update_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $new_password_confirm = $_POST['new_password_confirm'] ?? '';

        // جلب الهاش الحالي
        $stmt = $conn->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        $hash = $user_data['password_hash'];

        if (!password_verify($current_password, $hash)) {
            $errors[] = 'كلمة المرور الحالية غير صحيحة.';
        } elseif (strlen($new_password) < 8) {
            $errors[] = 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل.';
        } elseif ($new_password !== $new_password_confirm) {
            $errors[] = 'كلمة المرور الجديدة وتأكيدها غير متطابقتين.';
        } else {
            $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([$new_hash, $user_id]);
            $success = 'تم تحديث كلمة المرور بنجاح!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الملف الشخصي</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background:#f4f5f7; margin:0; padding:20px; direction:rtl; }
        .container { max-width: 600px; margin: 30px auto; background:#fff; border-radius:14px; padding:30px; box-shadow:0 8px 24px rgba(0,0,0,.08); }
        h2 { text-align:center; color:#101826; margin-bottom:30px; }
        .field-group { margin-bottom:25px; border:1px solid #ddd; padding:15px; border-radius:10px; }
        .field-group h3 { margin-top:0; color:#00a87a; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:15px; }
        label { display:block; font-size:14px; color:#333; margin-bottom:6px; font-weight:600; }
        input { width:100%; height:42px; border:1px solid #dcdfe4; border-radius:8px; padding:0 12px; margin-bottom:10px; }
        .btn { width:100%; height:42px; border:0; border-radius:10px; background:#101826; color:#fff; font-weight:700; cursor:pointer; margin-top:10px; }
        .btn:hover { background:#333; }
        .message.error { background:#ffe9e9; color:#a40000; padding:10px; border-radius:8px; margin-bottom:15px; font-size:14px; }
        .message.success { background:#e8fff1; color:#0b7a3b; padding:10px; border-radius:8px; margin-bottom:15px; font-size:14px; }
        .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #101826; font-weight: 600; }
    </style>
</head>
<body>
 <a href="dashboard1.php" class="back-link">← الرجوع إلى الرصيد الإجمالي</a>
    

    <div class="container">
        <h2>👤 المعلومات الشخصية</h2>
        ```

    <div class="container">
        <h2>الملف الشخصي والإعدادات</h2>

        <?php if ($success): ?><div class="message success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($errors): ?><div class="message error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div><?php endif; ?>

        <form method="post">
            <input type="hidden" name="action" value="update_info">
            <div class="field-group">
                <h3>تحديث الاسم والبريد الإلكتروني</h3>
                
                <label>الاسم/اسم المستخدم:</label>
                <input type="text" name="new_name" value="<?= htmlspecialchars($current_name) ?>" required>
                
                <label>البريد الإلكتروني:</label>
                <input type="email" name="new_email" value="<?= htmlspecialchars($current_email) ?>" required>
                
                <button class="btn" type="submit">حفظ التغييرات</button>
            </div>
        </form>

        <form method="post">
            <input type="hidden" name="action" value="update_password">
            <div class="field-group">
                <h3>تغيير كلمة المرور</h3>
                
                <label>كلمة المرور الحالية:</label>
                <input type="password" name="current_password" required>
                
                <label>كلمة المرور الجديدة (8 أحرف حد أدنى):</label>
                <input type="password" name="new_password" minlength="8" required>
                
                <label>تأكيد كلمة المرور الجديدة:</label>
                <input type="password" name="new_password_confirm" required>
                
                <button class="btn" type="submit">تغيير كلمة المرور</button>
            </div>
        </form>
        
        <div class="field-group">
            <h3>إعدادات إضافية</h3>
            <p><strong>اللغة:</strong> العربية (قيد التطوير)</p>
            <p><strong>التقييمات:</strong> يمكنك تقييم التطبيق لدعمنا! <a href="rate_app.php" class="back-link" style="color: #007bff;">⭐</a></p>
            <a href="rate_app.php" class="back-link" style="color: #007bff;"> تقييم التطبيق</a>
        </div>
    </div>
</body>
</html>