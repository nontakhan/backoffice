<?php
/**
 * Header Component
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

$user = getCurrentUser();
$greeting = 'สวัสดี';
$hour = (int)date('H');
if ($hour >= 5 && $hour < 12) {
    $greeting = 'สวัสดีตอนเช้า';
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = 'สวัสดีตอนบ่าย';
} else {
    $greeting = 'สวัสดีตอนเย็น';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Backoffice' ?> | <?= APP_NAME ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>assets/img/favicon.ico">
    
    <!-- Google Fonts - Sarabun -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    
    <?php if (isset($extraCSS)): ?>
        <?= $extraCSS ?>
    <?php endif; ?>
</head>
<body>
    <div class="app-wrapper">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="btn btn-ghost btn-icon d-lg-none" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="header-greeting">
                        <?= $greeting ?>, <span class="highlight"><?= $user['full_name'] ?? $user['username'] ?></span>
                    </h1>
                </div>
                
                <div class="header-right">
                    <!-- Theme Toggle -->
                    <button class="header-notification theme-toggle" id="themeToggle" title="สลับธีม">
                        <i class="fas fa-moon" id="themeIconDark"></i>
                        <i class="fas fa-sun" id="themeIconLight" style="display: none;"></i>
                    </button>
                    
                    <!-- Notifications -->
                    <div class="header-notification" id="notificationBtn">
                        <i class="fas fa-bell"></i>
                        <span class="badge" id="notificationBadge" style="display: none;">0</span>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="header-user" id="userMenuBtn">
                        <div class="header-user-avatar avatar-secondary">
                            <?php if (!empty($user['photo'])): ?>
                                <img src="<?= BASE_URL ?>uploads/photos/<?= $user['photo'] ?>" alt="">
                            <?php else: ?>
                                <?= mb_substr($user['full_name'] ?? $user['username'], 0, 1) ?>
                            <?php endif; ?>
                        </div>
                        <span class="header-user-name"><?= $user['full_name'] ?? $user['username'] ?></span>
                        <i class="fas fa-chevron-down text-muted"></i>
                    </div>
                </div>
            </header>
