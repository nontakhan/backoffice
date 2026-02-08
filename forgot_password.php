<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = sanitize($_POST['input'] ?? '');
    
    if (empty($input)) {
        $error = 'กรุณากรอกชื่อผู้ใช้หรืออีเมล';
    } else {
        $pdo = getDBConnection();
        
        // Find user by username or email (via employees table)
        $stmt = $pdo->prepare("
            SELECT u.user_id, u.username, e.email 
            FROM users u
            LEFT JOIN employees e ON u.employee_id = e.employee_id
            WHERE u.username = ? OR e.email = ?
            LIMIT 1
        ");
        $stmt->execute([$input, $input]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Save token
            $updateStmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE user_id = ?");
            if ($updateStmt->execute([$token, $expires, $user['user_id']])) {
                $success = 'ระบบได้สร้างลิงก์สำหรับเปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
                // Simulation: Display link
                $resetLink = BASE_URL . "reset_password_confirm.php?token=" . $token;
            } else {
                $error = 'เกิดข้อผิดพลาดในการสร้างลิงก์';
            }
        } else {
            // Security: Don't reveal if user exists, but for this internal app, maybe clear error is better?
            // Let's stick to generic message for security best practice, or specific if user insists.
            // Given "Help me fix login", clear feedback is mostly preferred in internal tools.
            $error = 'ไม่พบข้อมูลผู้ใช้ในระบบ';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน | <?= APP_NAME ?></title>
    <!-- Google Fonts - Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        .login-card { max-width: 450px; }
        .reset-link-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
            word-break: break-all;
            font-family: monospace;
            color: #166534;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <div class="login-logo-icon"><i class="fas fa-key"></i></div>
            <h1 class="login-title">ลืมรหัสผ่าน</h1>
            <p class="login-subtitle">กรอกชื่อผู้ใช้หรืออีเมลเพื่อรีเซ็ตรหัสผ่าน</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert-box error">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert-box info" style="background: #ecfdf5; border-color: #a7f3d0; color: #047857;">
            <i class="fas fa-check-circle"></i> <?= $success ?>
        </div>
        <?php if ($resetLink): ?>
        <div class="reset-link-box">
            <strong>Simulation Link (Click to testing):</strong><br>
            <a href="<?= $resetLink ?>"><?= $resetLink ?></a>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        
        <form class="login-form" method="POST" action="">
            <div class="input-group">
                <i class="fas fa-user-circle input-group-icon"></i>
                <input type="text" name="input" class="form-control" placeholder="ชื่อผู้ใช้ หรือ อีเมล" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-paper-plane"></i> ส่งคำขอ
            </button>
            
            <div class="login-divider">
                <span>หรือ</span>
            </div>
            
            <a href="index.php" class="btn btn-outline w-100" style="text-align:center; display:block; text-decoration:none;">
                กลับไปหน้าเข้าสู่ระบบ
            </a>
        </form>
    </div>
</body>
</html>
