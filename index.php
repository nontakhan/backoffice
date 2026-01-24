<?php
/**
 * Login Page
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } elseif (login($username, $password)) {
        redirect('dashboard.php');
    } else {
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}

// Get message
$msg = $_GET['msg'] ?? '';
$messages = [
    'login_required' => 'กรุณาเข้าสู่ระบบก่อนใช้งาน',
    'session_expired' => 'Session หมดอายุ กรุณาเข้าสู่ระบบใหม่',
    'logged_out' => 'ออกจากระบบเรียบร้อยแล้ว'
];
$infoMessage = $messages[$msg] ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | <?= APP_NAME ?></title>
    
    <!-- Google Fonts - Sarabun -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    
    <style>
        .login-form .input-group {
            margin-bottom: 1.25rem;
        }
        
        .login-form .form-control {
            height: 50px;
        }
        
        .login-form .btn {
            height: 50px;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .login-remember {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        
        .login-remember a {
            font-size: 0.875rem;
        }
        
        .login-divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }
        
        .login-divider::before,
        .login-divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: var(--border-color);
        }
        
        .login-divider::before { left: 0; }
        .login-divider::after { right: 0; }
        
        .login-divider span {
            background: var(--bg-card);
            padding: 0 1rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        
        .company-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        .company-list span {
            font-size: 0.75rem;
            color: var(--text-muted);
            padding: 0.375rem 0.75rem;
            background: rgba(255,255,255,0.03);
            border-radius: var(--radius-full);
            border: 1px solid var(--border-color);
        }
        
        .alert-box {
            padding: 0.875rem 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-box.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--danger-500);
        }
        
        .alert-box.info {
            background: rgba(6, 182, 212, 0.1);
            border: 1px solid rgba(6, 182, 212, 0.3);
            color: var(--secondary-400);
        }
    </style>
</head>
<body class="login-page">
    <div class="login-card">
        <!-- Logo -->
        <div class="login-logo">
            <div class="login-logo-icon">
                <i class="fas fa-building"></i>
            </div>
            <h1 class="login-title">NUMRUNG</h1>
            <p class="login-subtitle">ระบบ Backoffice กลุ่มบริษัทยะลานำรุ่ง</p>
        </div>
        
        <!-- Messages -->
        <?php if ($error): ?>
        <div class="alert-box error">
            <i class="fas fa-exclamation-circle"></i>
            <?= $error ?>
        </div>
        <?php endif; ?>
        
        <?php if ($infoMessage): ?>
        <div class="alert-box info">
            <i class="fas fa-info-circle"></i>
            <?= $infoMessage ?>
        </div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <form class="login-form" method="POST" action="">
            <div class="input-group">
                <i class="fas fa-user input-group-icon"></i>
                <input type="text" 
                       name="username" 
                       class="form-control" 
                       placeholder="ชื่อผู้ใช้"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       autocomplete="username"
                       required>
            </div>
            
            <div class="input-group">
                <i class="fas fa-lock input-group-icon"></i>
                <input type="password" 
                       name="password" 
                       class="form-control" 
                       placeholder="รหัสผ่าน"
                       autocomplete="current-password"
                       required>
            </div>
            
            <div class="login-remember">
                <label class="form-check">
                    <input type="checkbox" name="remember" class="form-check-input">
                    <span class="form-check-label">จดจำการเข้าสู่ระบบ</span>
                </label>
                <a href="#">ลืมรหัสผ่าน?</a>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt"></i>
                เข้าสู่ระบบ
            </button>
        </form>
        
        <!-- Company List -->
        <div class="company-list">
            <span>ยะลานำรุ่ง</span>
            <span>นำรุ่งเคหะภัณฑ์</span>
            <span>นำรุ่งพูล</span>
            <span>นำรุ่งคอนกรีต</span>
            <span>นำรุ่งธุรกิจ</span>
        </div>
    </div>
    
    <script>
        // Focus on username field
        document.querySelector('input[name="username"]').focus();
    </script>
</body>
</html>
