<?php
session_start();

$success = '';
$errors = [];

// إذا ضغط المستخدم على زر التقييم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'] ?? 0;
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $errors[] = 'الرجاء اختيار تقييم من 1 إلى 5 نجوم.';
    } else {
        // يمكنك هنا حفظ التقييم في قاعدة بيانات (جدول تقييمات)
        // في الوقت الحالي، سنعرض فقط رسالة نجاح:
        $success = 'شكراً لك! تم استلام تقييمك بنجاح وسنعمل على تطوير "ميزانيتي" للأفضل.';
        
        // يمكنك إضافة كود حفظ التقييم هنا لاحقاً:
        /* $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO ratings (user_id, rating, comment) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $rating, $comment]);
        */
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقييم ميزانيتي</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* (استخدم تنسيقاتك الفخمة من ملف profile.php) */
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background:#f4f5f7; margin:0; padding:20px; direction:rtl; }
        .container { max-width: 500px; margin: 30px auto; background:#fff; border-radius:14px; padding:30px; box-shadow:0 8px 24px rgba(0,0,0,.08); text-align: center;}
        h2 { color:#101826; margin-bottom:30px; }
        label { display:block; font-size:14px; color:#333; margin-bottom:6px; font-weight:600; text-align: right; }
        textarea { width:100%; min-height:100px; border:1px solid #dcdfe4; border-radius:8px; padding:12px; margin-bottom:10px; resize: vertical; }
        .btn { width:100%; height:42px; border:0; border-radius:10px; background:#00a87a; color:#fff; font-weight:700; cursor:pointer; margin-top:20px; }
        .btn:hover { background:#008f6a; }
        .message.success { background:#e8fff1; color:#0b7a3b; padding:15px; border-radius:8px; margin-bottom:15px; font-size:16px; border-right: 5px solid #0b7a3b; }
        .message.error { background:#ffe9e9; color:#a40000; padding:10px; border-radius:8px; margin-bottom:15px; font-size:14px; }
        .star-rating { margin-bottom: 20px; display: flex; justify-content: center; direction: ltr;}
        .star-rating input { display: none; }
        .star-rating label { font-size: 30px; padding: 5px; cursor: pointer; color: #ccc; transition: color 0.2s;}
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: #ffc107; }

        .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #101826; font-weight: 600; float: right; }
    </style>
</head>
<body>
<a href="dashboard1.php" class="back-link">← الرجوع إلى الرصيد الإجمالي</a>

    <div class="container">
        <h2>⭐️ تقييم تطبيق ميزانيتي</h2>

        <?php if ($success): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
            <a href="dashboard.php" class="btn" style="background: #101826;">العودة للرصيد</a>
        <?php else: ?>
            <?php if ($errors): ?><div class="message error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div><?php endif; ?>

            <form method="post">
                <label style="text-align: center; margin-bottom: 20px;">الرجاء تقييم التطبيق من 1 إلى 5 نجوم:</label>
                
                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5" required>
                    <label for="star5" title="ممتاز">★</label>
                    <input type="radio" id="star4" name="rating" value="4">
                    <label for="star4" title="جيد جداً">★</label>
                    <input type="radio" id="star3" name="rating" value="3">
                    <label for="star3" title="جيد">★</label>
                    <input type="radio" id="star2" name="rating" value="2">
                    <label for="star2" title="مقبول">★</label>
                    <input type="radio" id="star1" name="rating" value="1">
                    <label for="star1" title="سيئ">★</label>
                </div>

                <label for="comment">تعليق (اختياري):</label>
                <textarea id="comment" name="comment" placeholder="شاركنا رأيك ليساعدنا في التطوير..."></textarea>
                
                <button class="btn" type="submit">إرسال التقييم</button>
            </form>
        <?php endif; ?>

    </div>
</body>
</html>