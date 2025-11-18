<?php
// إعداد التوجيه: سيتم توجيه المستخدم إلى صفحة تسجيل الدخول/التحكم بعد 3 ثوانٍ
$redirect_url = 'auth.php'; // أو 'dashboard.php' إذا كان المستخدم مسجلاً
$delay_seconds = 3; // مدة ظهور الشاشة بالثواني (يمكنك تغييرها)

// إذا كنت تستخدم الجلسات للتحقق مما إذا كان المستخدم مسجلاً
session_start();
if (isset($_SESSION['user_id'])) {
    $redirect_url = 'dashboard.php'; // لو كان مسجلاً، وجهه مباشرة للوحة التحكم
} else {
    $redirect_url = 'auth.php'; // لو لم يكن مسجلاً، وجهه لصفحة الدخول
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ميزانيتي - مرحباً</title>
    <meta http-equiv="refresh" content="<?php echo $delay_seconds; ?>;url=<?php echo $redirect_url; ?>">
    <style>
        /* تنسيق الشاشة */
        body {
            background-color: #f4f5f7; /* لون الخلفية مطابق للخلفية في ملف auth.php */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #101826;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial;
            text-align: center;
        }
        .logo-container {
            animation: fadeIn 1.5s ease-in-out; /* إضافة تأثير ظهور سلس */
            margin-bottom: 20px;
        }
        .logo {
            width: 140px; /* حجم الشعار */
            height: auto;
        }
        h1 {
            font-weight: 800;
            font-size: 36px;
            margin: 0;
        }
        /* تعريف تأثير الظهور */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="logo-container">
        <img src="mizaniyati_logo.jpeg" alt="شعار ميزانيتي" class="logo">
    </div>
    <h1>ميزانيتي</h1>
    <p>تحميل...</p>
</body>
</html>