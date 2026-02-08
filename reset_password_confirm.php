<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$validToken = false;

if (empty($token)) {
    $error = 'ไม่พบ Token สำหรับรีเซ็ตรหัสผ่าน';
} else {
    $pdo = getDBConnection();
    // Validate token
    $stmt = $pdo->prepare("SELECT user_id, username FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $validToken = true;
    } else {
        $error = 'ลิงก์รีเซ็ตรหัสผ่านไม่ถูกต้อง หรือหมดอายุแล้ว';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 4) {
        $error = 'รหัสผ่านต้องมีความยาวอย่างน้อย 4 ตัวอักษร';
    } elseif ($password !== $confirmPassword) {
        $error = 'รหัสผ่านยืนยันไม่ตรงกัน';
    } else {
        // Update password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE user_id = ?");
        
        if ($updateStmt->execute([$hashedPassword, $user['user_id']])) {
            $success = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
            $validToken = false; // Disable form
        } else {
            $error = 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งรหัสผ่านใหม่ | <?= APP_NAME ?></title>
    <!-- Google Fonts - Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        .login-card { max-width: 450px; }
    </style>
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <div class="login-logo-icon"><i class="fas fa-lock"></i></div>
            <h1 class="login-title">ตั้งรหัสผ่านใหม่</h1>
            <?php if ($validToken): ?>
            <p class="login-subtitle">สำหรับคุณ: <strong><?= htmlspecialchars($user['username']) ?></strong></p>
            <?php endif; ?>
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
        <a href="index.php" class="btn btn-primary w-100 mt-3">กลับไปหน้าเข้าสู่ระบบ</a>
        <?php endif; ?>
        
        <?php if ($validToken): ?>
        <form class="login-form" method="POST" action="">
            <div class="input-group">
                <i class="fas fa-key input-group-icon"></i>
                <input type="password" name="password" class="form-control" placeholder="รหัสผ่านใหม่" required autofocus>
            </div>
            
            <div class="input-group">
                <i class="fas fa-check-double input-group-icon"></i>
                <input type="password" name="confirm_password" class="form-control" placeholder="ยืนยันรหัสผ่านใหม่" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save"></i> บันทึกรหัสผ่านใหม่
            </button>
        </form>
        <?php endif; ?>
        
        <?php if (!$validToken && !$success): ?>
        <a href="forgot_password.php" class="btn btn-outline w-100">ขอรีเซ็ตรหัสผ่านใหม่</a>
        <a href="index.php" class="btn btn-link w-100 mt-2">กลับไปหน้าเข้าสู่ระบบ</a>
        <?php endif; ?>
    </div>
</body>
</html>
