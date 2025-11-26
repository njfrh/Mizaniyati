<?php
session_start();
require_once 'db.php';

// إعادة توجيه إذا لم يكن هناك reset_user_id في السيشن
if (empty($_SESSION['reset_user_id'])) {
    header('Location: forgot_password.php');
    exit;
}

$errors = [];
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p1 = $_POST['password'] ?? '';
    $p2 = $_POST['password_confirm'] ?? '';

    // شروط الباسوورد: أرقام فقط وطول 6 أو أكثر
    if (!preg_match('/^[0-9]{6,}$/', $p1)) {
        $errors[] = 'Password must be numbers only and at least 6 digits.';
    } elseif ($p1 !== $p2) {
        $errors[] = 'Passwords do not match.';
    } else {
        $hash    = password_hash($p1, PASSWORD_BCRYPT);
        $user_id = (int)$_SESSION['reset_user_id'];

        $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->bind_param('si', $hash, $user_id);
        $stmt->execute();
        $stmt->close();

        // حذف السيشن بعد التحديث
        unset($_SESSION['reset_user_id']);
        $ok = 'Password updated. You can login now.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Set new password</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: "Cairo", system-ui, -apple-system, Segoe UI, Roboto, Arial;
        }

        body {
            margin: 0;
            background: linear-gradient(135deg, #2AB7A9, #1E8E82 65%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .card {
            width: 380px;
            background: #fff;
            border-radius: 22px;
            padding: 32px 28px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
            animation: fadeIn .5s ease;
        }

        .brand {
            text-align: center;
            font-weight: 800;
            font-size: 28px;
            margin-bottom: 14px;
            color: #116B63;
        }

        .hint {
            text-align: center;
            color: #667;
            font-size: 14px;
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 13px;
            color: #116B63;
            font-weight: 600;
            margin-bottom: 6px;
        }

        input {
            width: 100%;
            height: 46px;
            border: 1.7px solid #c8e9e6;
            border-radius: 12px;
            padding: 0 14px;
            font-size: 15px;
            transition: .25s;
            outline: none;
        }

        input:focus {
            border-color: #2AB7A9;
            box-shadow: 0 0 0 3px rgba(42,183,169,0.20);
        }

        .btn {
            width: 100%;
            height: 48px;
            border: 0;
            border-radius: 12px;
            background: #2AB7A9;
            color: #fff;
            font-size: 17px;
            font-weight: 800;
            cursor: pointer;
            transition: .25s;
            margin-top: 12px;
        }

        .btn:hover {
            background: #1E8E82;
        }

        .err {
            padding: 12px;
            background: #ffe8e8;
            border: 1px solid #ffb9b9;
            color: #b10000;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .ok {
            padding: 12px;
            background: #e8fff3;
            border: 1px solid #a4ffce;
            color: #087c47;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .back {
            text-align: center;
            margin-top: 18px;
        }

        .back a {
            color: #2AB7A9;
            text-decoration: none;
            font-weight: 700;
        }

        .back a:hover {
            text-decoration: underline;
        }

        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            cursor: pointer;
            color: #116B63;
            opacity: .75;
            transition: .2s;
        }

        .toggle-password:hover {
            opacity: 1;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">Set New Password</div>

        <?php if ($ok): ?>
            <div class="ok"><?= htmlspecialchars($ok) ?></div>
            <p class="back"><a href="auth.php?tab=login">Go to login</a></p>
        <?php else: ?>
            <?php if ($errors): ?>
                <div class="err"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
            <?php endif; ?>

            <form method="post">
                <label>New password</label>
                <div class="password-wrapper">
                    <input type="password" id="new_password" name="password" minlength="6" pattern="[0-9]{6,}" required>
                    <span class="toggle-password" id="icon_new" onclick="togglePassword('new_password','icon_new')">👁️</span>
                </div>
                <br>

                <label>Confirm password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="password_confirm" minlength="6" pattern="[0-9]{6,}" required>
                    <span class="toggle-password" id="icon_confirm" onclick="togglePassword('confirm_password','icon_confirm')">👁️</span>
                </div>
                <br>

                <button type="submit" class="btn">Update password</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        function togglePassword(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon  = document.getElementById(iconId);
            if (!field) return;

            if (field.type === 'password') {
                field.type = 'text';
                icon.textContent = '🙈';
            } else {
                field.type = 'password';
                icon.textContent = '👁️';
            }
        }
    </script>
</body>
</html>
