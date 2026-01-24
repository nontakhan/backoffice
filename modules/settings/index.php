<?php
/**
 * Settings Index
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$pageTitle = 'ตั้งค่าระบบ';
$pdo = getDBConnection();

// Get counts
$stmt = $pdo->query("SELECT COUNT(*) FROM companies WHERE status = 1");
$companyCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM branches WHERE status = 1");
$branchCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM positions WHERE status = 1");
$positionCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM leave_types WHERE status = 1");
$leaveTypeCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 1");
$userCount = $stmt->fetchColumn();

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Content -->
<div class="page-content">
    <h2 class="mb-4">ตั้งค่าระบบ</h2>
    
    <div class="grid grid-cols-3">
        <!-- Companies -->
        <a href="companies.php" class="card" style="text-decoration: none;">
            <div class="card-body text-center">
                <div class="avatar avatar-xl avatar-primary mx-auto mb-3">
                    <i class="fas fa-building"></i>
                </div>
                <h4>บริษัท</h4>
                <p class="text-muted mb-0"><?= $companyCount ?> บริษัท</p>
            </div>
        </a>
        
        <!-- Branches -->
        <a href="branches.php" class="card" style="text-decoration: none;">
            <div class="card-body text-center">
                <div class="avatar avatar-xl avatar-secondary mx-auto mb-3">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h4>สาขา</h4>
                <p class="text-muted mb-0"><?= $branchCount ?> สาขา</p>
            </div>
        </a>
        
        <!-- Positions -->
        <a href="positions.php" class="card" style="text-decoration: none;">
            <div class="card-body text-center">
                <div class="avatar avatar-xl avatar-accent mx-auto mb-3">
                    <i class="fas fa-id-badge"></i>
                </div>
                <h4>ตำแหน่งงาน</h4>
                <p class="text-muted mb-0"><?= $positionCount ?> ตำแหน่ง</p>
            </div>
        </a>
        
        <!-- Leave Types -->
        <a href="leave_types.php" class="card" style="text-decoration: none;">
            <div class="card-body text-center">
                <div class="avatar avatar-xl mx-auto mb-3" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h4>ประเภทการลา</h4>
                <p class="text-muted mb-0"><?= $leaveTypeCount ?> ประเภท</p>
            </div>
        </a>
        
        <!-- Users -->
        <a href="users.php" class="card" style="text-decoration: none;">
            <div class="card-body text-center">
                <div class="avatar avatar-xl mx-auto mb-3" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h4>ผู้ใช้ระบบ</h4>
                <p class="text-muted mb-0"><?= $userCount ?> คน</p>
            </div>
        </a>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
