<?php
session_start();
require_once 'db.php'; // اتصال قاعدة البيانات

$errors = [];
$success = '';

// نفترض إن عندك الاسم في الجلسة
$user_id   = $_SESSION['user_id']   ?? null;
$user_name = $_SESSION['user_name'] ?? 'مستخدم';

// حفظ التقييم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating  = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    // لو مافي تقييم صحيح
    if ($rating < 1 || $rating > 5) {
        if ($comment !== '') {
            // كتب تعليق بدون ما يختار نجوم
            $errors[] = 'لازم تختار تقييم (من 1 إلى 5 نجوم) قبل ما ترسل التعليق.';
        } else {
            // لا تعليق ولا تقييم
            $errors[] = 'الرجاء اختيار تقييم من 1 إلى 5 نجوم.';
        }
    } else {
        // كل شيء تمام → نحفظ التقييم
        $stmt = $conn->prepare("INSERT INTO ratings (user_id, user_name, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $user_id, $user_name, $rating, $comment);
        $stmt->execute();
        $stmt->close();

        $success = 'شكراً لك! تم استلام تقييمك ♥️';
    }
}

// جلب كل التقييمات علشان نعرضها تحت الفورم
$allRatings = $conn->query("SELECT user_name, rating, comment, created_at FROM ratings ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقييم ميزانيتي</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background:#f4f5f7; margin:0; padding:20px; direction:rtl; }
        .container { max-width: 500px; margin: 30px auto; background:#fff; border-radius:14px; padding:30px; box-shadow:0 8px 24px rgba(0,0,0,.08); text-align: center;}
        h2 { color:#101826; margin-bottom:20px; }
        label { display:block; font-size:14px; color:#333; margin-bottom:6px; font-weight:600; text-align: right; }
        textarea { width:100%; min-height:100px; border:1px solid #dcdfe4; border-radius:8px; padding:12px; margin-bottom:10px; resize: vertical; }
        .btn { width:100%; height:42px; border:0; border-radius:10px; background:#00a87a; color:#fff; font-weight:700; cursor:pointer; margin-top:20px; }
        .btn:hover { background:#008f6a; }
        .message.error { background:#ffe9e9; color:#a40000; padding:10px; border-radius:8px; margin-bottom:15px; font-size:14px; }
        .message.success { background:#e8fff1; color:#0b7a3b; padding:10px; border-radius:8px; margin-bottom:15px; font-size:14px; }

        .star-rating { margin-bottom: 20px; display: flex; justify-content: center; direction: ltr;}
        .star-rating input { display: none; }
        .star-rating label { font-size: 30px; padding: 5px; cursor: pointer; color: #ccc; transition: color 0.2s;}
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: #ffc107; }

        .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #101826; font-weight: 600; float: right; }

        /* جزء عرض التقييمات تحت */
        .ratings-list { text-align:right; margin-top:25px; }
        .rating-item { border-top:1px solid #eee; padding:10px 0; }
        .rating-item:first-child { border-top:none; }
        .r-name { font-weight:700; color:#101826; }
        .r-date { font-size:12px; color:#888; }
        .r-stars { color:#ffc107; margin:3px 0; }
        .r-comment { font-size:14px; color:#444; margin-top:3px; }
    </style>
</head>
<body>
<a href="dashboard1.php" class="back-link">← الرجوع إلى الرصيد الإجمالي</a>

    <div class="container">
        <h2>⭐️ تقييم موقع ميزانيتي</h2>

        <?php if ($success): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="message error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
        <?php endif; ?>

        <!-- فورم التقييم -->
        <form method="post">
            <label style="text-align: center; margin-bottom: 20px;">الرجاء تقييم الموقع من 1 إلى 5 نجوم:</label>
            
            <div class="star-rating">
                <!-- شلت required من هنا -->
                <input type="radio" id="star5" name="rating" value="5">
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

        <!-- التقييمات تحت الفورم -->
        <div class="ratings-list">
            <h3 style="text-align:center; margin-top:30px;">آراء المستخدمين</h3>

            <?php if ($allRatings->num_rows === 0): ?>
                <p style="text-align:center;">لا توجد تقييمات حتى الآن.</p>
            <?php else: ?>
                <?php while ($row = $allRatings->fetch_assoc()): ?>
                    <div class="rating-item">
                        <div class="r-name">
                            <?= htmlspecialchars($row['user_name']) ?>
                            <span class="r-date"> ـ <?= htmlspecialchars($row['created_at']) ?></span>
                        </div>
                        <div class="r-stars">
                            <?php
                                $r = (int)$row['rating'];
                                for ($i = 0; $i < $r; $i++) echo '★';
                                for ($i = $r; $i < 5; $i++) echo '☆';
                            ?>
                        </div>
                        <?php if (!empty($row['comment'])): ?>
                            <div class="r-comment"><?= nl2br(htmlspecialchars($row['comment'])) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>