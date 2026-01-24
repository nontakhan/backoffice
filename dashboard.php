<?php
/**
 * Dashboard
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pageTitle = 'หน้าหลัก';
$pdo = getDBConnection();

// Get statistics
$stats = [];

// Total companies
$stmt = $pdo->query("SELECT COUNT(*) FROM companies WHERE status = 1");
$stats['companies'] = $stmt->fetchColumn();

// Total employees
$companyFilter = '';
$params = [];
if (!isAdmin()) {
    $companyFilter = " AND e.company_id = ?";
    $params[] = $_SESSION['company_id'];
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM employees e WHERE e.status = 'active'" . $companyFilter);
$stmt->execute($params);
$stats['employees'] = $stmt->fetchColumn();

// Gender stats
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END) as male,
        SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) as female
    FROM employees e 
    WHERE e.status = 'active'" . $companyFilter);
$stmt->execute($params);
$genderStats = $stmt->fetch();
$stats['male'] = $genderStats['male'] ?? 0;
$stats['female'] = $genderStats['female'] ?? 0;

// Get companies with branches and employee counts per branch
$stmt = $pdo->query("
    SELECT c.company_id, c.company_name, c.company_code,
           (SELECT COUNT(*) FROM employees WHERE company_id = c.company_id AND status = 'active') as total_employees
    FROM companies c
    WHERE c.status = 1
    ORDER BY c.company_id
");
$companies = $stmt->fetchAll();

// Get branches with employee counts for each company
$branchStmt = $pdo->prepare("
    SELECT b.branch_id, b.branch_name,
           (SELECT COUNT(*) FROM employees WHERE branch_id = b.branch_id AND status = 'active') as employee_count
    FROM branches b
    WHERE b.company_id = ? AND b.status = 1
    ORDER BY b.branch_id
");

foreach ($companies as &$company) {
    $branchStmt->execute([$company['company_id']]);
    $company['branches_data'] = $branchStmt->fetchAll();
}

// Get pending leave requests (for supervisor/HR)
$pendingLeaves = 0;
if (isSupervisor()) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.employee_id
        WHERE lr.supervisor_status = 'pending'
        AND e.branch_id = ?
    ");
    $stmt->execute([$_SESSION['branch_id']]);
    $pendingLeaves = $stmt->fetchColumn();
}

if (isHR()) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.employee_id
        WHERE lr.supervisor_status = 'approved'
        AND lr.hr_status = 'pending'
        AND e.company_id = ?
    ");
    $stmt->execute([$_SESSION['company_id']]);
    $pendingLeaves += $stmt->fetchColumn();
}

include __DIR__ . '/includes/header.php';
?>

<!-- Page Content -->
<div class="page-content">
    <!-- Stats Cards -->
    <div class="grid grid-cols-3 mb-4">
        <!-- Companies -->
        <div class="stat-card">
            <div class="stat-card-icon primary">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-card-content">
                <div class="stat-card-label">บริษัท</div>
                <div class="stat-card-value"><?= number_format($stats['companies']) ?></div>
            </div>
        </div>
        
        <!-- Employees -->
        <div class="stat-card">
            <div class="stat-card-icon secondary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-card-content">
                <div class="stat-card-label">พนักงานทั้งหมด</div>
                <div class="stat-card-value"><?= number_format($stats['employees']) ?></div>
            </div>
        </div>
        
        <!-- Gender -->
        <div class="stat-card">
            <div class="stat-card-icon accent">
                <i class="fas fa-venus-mars"></i>
            </div>
            <div class="stat-card-content">
                <div class="stat-card-label">เพศ</div>
                <div class="stat-card-value">
                    <span class="d-flex align-items-center gap-3">
                        <span><i class="fas fa-mars text-secondary"></i> <?= number_format($stats['male']) ?></span>
                        <span><i class="fas fa-venus text-primary"></i> <?= number_format($stats['female']) ?></span>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($pendingLeaves > 0): ?>
    <!-- Pending Leaves Alert -->
    <div class="alert alert-warning mb-4">
        <i class="fas fa-exclamation-triangle alert-icon"></i>
        <div>
            <strong>มีใบลารออนุมัติ <?= $pendingLeaves ?> รายการ</strong>
            <p class="mb-0 mt-1">
                <a href="<?= BASE_URL ?>modules/leaves/approve.php" class="text-warning">
                    <i class="fas fa-arrow-right"></i> ไปยังหน้าอนุมัติลา
                </a>
            </p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Companies Grid -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-building"></i>
                บริษัทในเครือ
            </h3>
            <?php if (isAdmin()): ?>
            <a href="<?= BASE_URL ?>modules/settings/companies.php" class="btn btn-sm btn-outline">
                <i class="fas fa-cog"></i> จัดการ
            </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-3">
                <?php foreach ($companies as $company): ?>
                <div class="company-card" onclick="window.location.href='<?= BASE_URL ?>modules/employees/?company_id=<?= $company['company_id'] ?>'">
                    <h4 class="company-card-title">
                        <i class="fas fa-building me-2"></i>
                        <?= htmlspecialchars($company['company_name']) ?>
                    </h4>
                    <div class="company-card-branches">
                        <?php foreach ($company['branches_data'] as $branch): ?>
                        <div class="company-card-branch d-flex justify-content-between">
                            <span>
                                <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                <?= htmlspecialchars($branch['branch_name']) ?>
                            </span>
                            <span class="badge badge-sm" style="background: rgba(255,255,255,0.1); font-size: 0.75rem;">
                                <?= number_format($branch['employee_count']) ?> คน
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="company-card-footer mt-auto pt-3" style="border-top: 1px solid var(--border-color);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted fs-sm">
                                <i class="fas fa-users"></i> รวมพนักงาน
                            </span>
                            <span class="fw-bold" style="color: var(--primary-300);">
                                <?= number_format($company['total_employees']) ?> คน
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
