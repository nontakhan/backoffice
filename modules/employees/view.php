<?php
/**
 * Employee Profile View
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$employeeId = (int)($_GET['id'] ?? 0);

if (!$employeeId) {
    redirect('modules/employees/');
}

// Check access permission
if (!canAccessEmployee($employeeId)) {
    redirect('modules/employees/?msg=access_denied');
}

$pdo = getDBConnection();

// Get employee data
$stmt = $pdo->prepare("
    SELECT e.*, 
           c.company_name, b.branch_name, d.department_name, p.position_name,
           CONCAT(s.prefix, s.first_name, ' ', s.last_name) as supervisor_name,
           TIMESTAMPDIFF(YEAR, e.start_date, CURDATE()) as work_years,
           TIMESTAMPDIFF(MONTH, e.start_date, CURDATE()) % 12 as work_months
    FROM employees e
    LEFT JOIN companies c ON e.company_id = c.company_id
    LEFT JOIN branches b ON e.branch_id = b.branch_id
    LEFT JOIN departments d ON e.department_id = d.department_id
    LEFT JOIN positions p ON e.position_id = p.position_id
    LEFT JOIN employees s ON e.supervisor_id = s.employee_id
    WHERE e.employee_id = ?
");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch();

if (!$employee) {
    redirect('modules/employees/?msg=not_found');
}

// Get transfer history
$stmt = $pdo->prepare("
    SELECT t.*,
           fc.company_name as from_company, tc.company_name as to_company,
           fb.branch_name as from_branch, tb.branch_name as to_branch
    FROM employee_transfers t
    LEFT JOIN companies fc ON t.from_company_id = fc.company_id
    LEFT JOIN companies tc ON t.to_company_id = tc.company_id
    LEFT JOIN branches fb ON t.from_branch_id = fb.branch_id
    LEFT JOIN branches tb ON t.to_branch_id = tb.branch_id
    WHERE t.employee_id = ?
    ORDER BY t.transfer_date DESC
");
$stmt->execute([$employeeId]);
$transfers = $stmt->fetchAll();

// Get training history
$stmt = $pdo->prepare("
    SELECT * FROM trainings 
    WHERE employee_id = ? 
    ORDER BY training_date DESC
");
$stmt->execute([$employeeId]);
$trainings = $stmt->fetchAll();

$pageTitle = $employee['prefix'] . $employee['first_name'] . ' ' . $employee['last_name'];

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Content -->
<div class="page-content">
    <!-- Breadcrumb -->
    <nav class="mb-4">
        <a href="<?= BASE_URL ?>modules/employees/" class="text-muted">
            <i class="fas fa-arrow-left"></i> กลับไปรายชื่อพนักงาน
        </a>
    </nav>
    
    <div class="grid" style="grid-template-columns: 350px 1fr; gap: 1.5rem;">
        <!-- Left Column - Profile Card -->
        <div>
            <div class="card">
                <div class="profile-header">
                    <div class="avatar avatar-xxl avatar-primary profile-avatar">
                        <?php if ($employee['photo']): ?>
                            <img src="<?= BASE_URL ?>uploads/photos/<?= htmlspecialchars($employee['photo']) ?>" alt="">
                        <?php else: ?>
                            <?= mb_substr($employee['first_name'], 0, 1) ?>
                        <?php endif; ?>
                    </div>
                    
                    <h2 class="profile-name">
                        <?= htmlspecialchars($employee['prefix'] . $employee['first_name'] . ' ' . $employee['last_name']) ?>
                    </h2>
                    
                    <span class="profile-role">
                        <?= htmlspecialchars($employee['position_name'] ?? 'ไม่ระบุตำแหน่ง') ?>
                    </span>
                    
                    <div class="profile-work-years">
                        อายุงาน <span><?= $employee['work_years'] ?></span> ปี 
                        <?php if ($employee['work_months']): ?>
                        <span><?= $employee['work_months'] ?></span> เดือน
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fas fa-building text-primary" style="width: 20px;"></i>
                            <span><?= htmlspecialchars($employee['company_name']) ?></span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <i class="fas fa-map-marker-alt text-secondary" style="width: 20px;"></i>
                            <span><?= htmlspecialchars($employee['branch_name']) ?></span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <i class="fas fa-sitemap text-accent" style="width: 20px;"></i>
                            <span><?= htmlspecialchars($employee['department_name'] ?? '-') ?></span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <i class="fas fa-phone text-success" style="width: 20px;"></i>
                            <span><?= htmlspecialchars($employee['phone'] ?? '-') ?></span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <i class="fas fa-envelope text-warning" style="width: 20px;"></i>
                            <span><?= htmlspecialchars($employee['email'] ?? '-') ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if (isHR()): ?>
                <div class="card-footer">
                    <div class="d-flex gap-2">
                        <a href="edit.php?id=<?= $employeeId ?>" class="btn btn-primary flex-1">
                            <i class="fas fa-edit"></i> แก้ไข
                        </a>
                        <a href="transfer.php?id=<?= $employeeId ?>" class="btn btn-secondary">
                            <i class="fas fa-exchange-alt"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Right Column - Details -->
        <div>
            <!-- Personal Info -->
            <div class="info-box">
                <div class="info-box-header">
                    <h4 class="info-box-title">
                        <i class="fas fa-user"></i> ข้อมูลส่วนบุคคล
                    </h4>
                </div>
                <div class="info-box-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">เลขบัตรประชาชน</span>
                            <span class="info-value"><?= htmlspecialchars($employee['citizen_id'] ?? '-') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">วันเดือนปีเกิด</span>
                            <span class="info-value"><?= formatThaiDate($employee['birth_date']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">เพศ</span>
                            <span class="info-value"><?= $employee['gender'] === 'male' ? 'ชาย' : 'หญิง' ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">หมู่เลือด</span>
                            <span class="info-value"><?= htmlspecialchars($employee['blood_type'] ?? '-') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ศาสนา</span>
                            <span class="info-value"><?= htmlspecialchars($employee['religion'] ?? '-') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">สถานภาพ</span>
                            <span class="info-value"><?= htmlspecialchars($employee['marital_status'] ?? '-') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ระดับการศึกษา</span>
                            <span class="info-value"><?= htmlspecialchars($employee['education_level'] ?? '-') ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div class="info-box">
                <div class="info-box-header">
                    <h4 class="info-box-title">
                        <i class="fas fa-address-book"></i> ข้อมูลการติดต่อ
                    </h4>
                </div>
                <div class="info-box-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">เบอร์โทรศัพท์มือถือ</span>
                            <span class="info-value"><?= htmlspecialchars($employee['phone'] ?? '-') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">อีเมล</span>
                            <span class="info-value"><?= htmlspecialchars($employee['email'] ?? '-') ?></span>
                        </div>
                        <div class="info-item" style="grid-column: span 2;">
                            <span class="info-label">ที่อยู่ปัจจุบัน</span>
                            <span class="info-value"><?= htmlspecialchars($employee['address'] ?? '-') ?></span>
                        </div>
                    </div>
                    
                    <hr style="border-color: var(--border-color); margin: 1rem 0;">
                    
                    <p class="text-muted fs-sm mb-2">ข้อมูลผู้ติดต่อฉุกเฉิน</p>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">ชื่อผู้ติดต่อฉุกเฉิน</span>
                            <span class="info-value"><?= htmlspecialchars($employee['emergency_contact_name'] ?? '-') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ความสัมพันธ์</span>
                            <span class="info-value"><?= htmlspecialchars($employee['emergency_contact_relation'] ?? '-') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">เบอร์ติดต่อฉุกเฉิน</span>
                            <span class="info-value"><?= htmlspecialchars($employee['emergency_contact_phone'] ?? '-') ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Work Info -->
            <div class="info-box">
                <div class="info-box-header">
                    <h4 class="info-box-title">
                        <i class="fas fa-briefcase"></i> ข้อมูลการทำงาน
                    </h4>
                </div>
                <div class="info-box-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">วันที่เริ่มงาน</span>
                            <span class="info-value"><?= formatThaiDate($employee['start_date']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ตำแหน่ง</span>
                            <span class="info-value"><?= htmlspecialchars($employee['position_name'] ?? '-') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ประเภท</span>
                            <span class="info-value">
                                <?= $employee['employee_type'] === 'monthly' ? 'พนักงานรายเดือน' : 'พนักงานรายวัน' ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">บริษัท</span>
                            <span class="info-value"><?= htmlspecialchars($employee['company_name']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">แผนก</span>
                            <span class="info-value"><?= htmlspecialchars($employee['department_name'] ?? '-') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">สาขา</span>
                            <span class="info-value"><?= htmlspecialchars($employee['branch_name']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">หัวหน้างาน</span>
                            <span class="info-value"><?= htmlspecialchars($employee['supervisor_name'] ?? '-') ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Training History -->
            <div class="info-box">
                <div class="info-box-header">
                    <h4 class="info-box-title">
                        <i class="fas fa-graduation-cap"></i> ประวัติการฝึกอบรม
                    </h4>
                    <?php if (isHR()): ?>
                    <button class="btn btn-sm btn-outline" onclick="showAddTrainingModal()">
                        <i class="fas fa-plus"></i> เพิ่ม
                    </button>
                    <?php endif; ?>
                </div>
                <div class="info-box-body">
                    <?php if (empty($trainings)): ?>
                    <p class="text-muted text-center py-3">ยังไม่มีประวัติการฝึกอบรม</p>
                    <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">ลำดับ</th>
                                    <th style="width: 120px;">วันที่อบรม</th>
                                    <th>หลักสูตร</th>
                                    <th style="width: 100px;">เอกสาร</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trainings as $index => $training): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= formatThaiDate($training['training_date']) ?></td>
                                    <td><?= htmlspecialchars($training['course_name']) ?></td>
                                    <td>
                                        <?php if ($training['certificate_file']): ?>
                                        <a href="<?= BASE_URL ?>uploads/certificates/<?= htmlspecialchars($training['certificate_file']) ?>" 
                                           target="_blank" class="btn btn-sm btn-outline">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <?php else: ?>
                                        -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Transfer History -->
            <?php if (!empty($transfers)): ?>
            <div class="info-box">
                <div class="info-box-header">
                    <h4 class="info-box-title">
                        <i class="fas fa-exchange-alt"></i> ประวัติการย้ายหน่วยงาน
                    </h4>
                </div>
                <div class="info-box-body">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 120px;">วันที่ย้าย</th>
                                    <th>จาก</th>
                                    <th>ไป</th>
                                    <th>เหตุผล</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transfers as $transfer): ?>
                                <tr>
                                    <td><?= formatThaiDate($transfer['transfer_date']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($transfer['from_company'] ?? '-') ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($transfer['from_branch'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($transfer['to_company'] ?? '-') ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($transfer['to_branch'] ?? '') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($transfer['reason'] ?? '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
