<?php
/**
 * Sidebar Component
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>

<aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-logo">N</div>
        <span class="sidebar-brand-text">NUMRUNG</span>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <!-- Main Menu -->
        <div class="nav-section">
            <a href="<?= BASE_URL ?>dashboard.php" class="nav-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i>
                <span>หน้าหลัก</span>
            </a>
        </div>

        <!-- Employee Section -->
        <div class="nav-section">
            <div class="nav-section-title">ระบบบุคคล</div>
            <a href="<?= BASE_URL ?>modules/employees/" class="nav-item <?= $currentDir === 'employees' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>ทะเบียนประวัติ</span>
            </a>
        </div>

        <!-- Payroll Section -->
        <div class="nav-section">
            <div class="nav-section-title">ระบบเงินเดือน</div>
            <a href="<?= BASE_URL ?>modules/payroll/" class="nav-item <?= $currentDir === 'payroll' ? 'active' : '' ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>ระบบเงินเดือน</span>
            </a>
        </div>

        <!-- Leave Section -->
        <div class="nav-section">
            <div class="nav-section-title">ระบบการปฏิบัติงาน</div>
            
            <div class="nav-item" onclick="toggleSubmenu('leave-menu')">
                <i class="fas fa-calendar-alt"></i>
                <span>ระบบการลา</span>
                <i class="fas fa-chevron-right nav-arrow"></i>
            </div>
            <div class="nav-submenu" id="leave-menu">
                <a href="<?= BASE_URL ?>modules/leaves/request.php" class="nav-item">
                    <span>ยื่นใบลา</span>
                </a>
                <a href="<?= BASE_URL ?>modules/leaves/" class="nav-item">
                    <span>รายการลา</span>
                </a>
                <?php if (isSupervisor()): ?>
                <a href="<?= BASE_URL ?>modules/leaves/approve.php" class="nav-item">
                    <span>อนุมัติลา</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Evaluation Section -->
        <?php if (isHR()): ?>
        <div class="nav-section">
            <div class="nav-item" onclick="toggleSubmenu('eval-menu')">
                <i class="fas fa-chart-bar"></i>
                <span>ระบบประเมิน</span>
                <i class="fas fa-chevron-right nav-arrow"></i>
            </div>
            <div class="nav-submenu" id="eval-menu">
                <a href="#" class="nav-item">
                    <span>ประเมินพนักงาน</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reports Section -->
        <?php if (isHR()): ?>
        <div class="nav-section">
            <a href="<?= BASE_URL ?>modules/payroll/payslip.php" class="nav-item">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>ระบบใบเดือน</span>
            </a>
            
            <a href="<?= BASE_URL ?>modules/reports/activity.php" class="nav-item">
                <i class="fas fa-history"></i>
                <span>ปฏิทินกิจกรรม</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- Settings Section -->
        <?php if (isAdmin()): ?>
        <div class="nav-section">
            <div class="nav-section-title">ตั้งค่าระบบ</div>
            <a href="<?= BASE_URL ?>modules/settings/" class="nav-item <?= $currentDir === 'settings' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span>ตั้งค่า</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- Logout -->
        <div class="nav-section" style="margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border-color);">
            <a href="<?= BASE_URL ?>logout.php" class="nav-item" onclick="return confirm('ต้องการออกจากระบบหรือไม่?')">
                <i class="fas fa-sign-out-alt"></i>
                <span>ออกจากระบบ</span>
            </a>
        </div>
    </nav>
</aside>

<script>
function toggleSubmenu(id) {
    const submenu = document.getElementById(id);
    const parentItem = submenu.previousElementSibling;
    
    submenu.classList.toggle('open');
    parentItem.classList.toggle('open');
}

// Open submenu if current page is in it
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const submenus = document.querySelectorAll('.nav-submenu');
    
    submenus.forEach(submenu => {
        const links = submenu.querySelectorAll('a');
        links.forEach(link => {
            if (currentPath.includes(link.getAttribute('href'))) {
                submenu.classList.add('open');
                submenu.previousElementSibling.classList.add('open');
            }
        });
    });
});
</script>
