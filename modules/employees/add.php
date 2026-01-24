<?php
/**
 * Add/Edit Employee
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'hr']);

$employeeId = (int)($_GET['id'] ?? 0);
$isEdit = $employeeId > 0;
$pdo = getDBConnection();

$employee = null;
if ($isEdit) {
    if (!canAccessEmployee($employeeId)) {
        redirect('modules/employees/?msg=access_denied');
    }
    
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        redirect('modules/employees/?msg=not_found');
    }
}

// Get companies
$accessibleCompanyIds = getAccessibleCompanyIds();
$placeholders = implode(',', array_fill(0, count($accessibleCompanyIds), '?'));
$stmt = $pdo->prepare("SELECT company_id, company_name FROM companies WHERE status = 1 AND company_id IN ($placeholders)");
$stmt->execute($accessibleCompanyIds);
$companies = $stmt->fetchAll();

// Get positions
$stmt = $pdo->query("SELECT position_id, position_name FROM positions WHERE status = 1 ORDER BY position_name");
$positions = $stmt->fetchAll();

$pageTitle = $isEdit ? 'แก้ไขข้อมูลพนักงาน' : 'เพิ่มพนักงานใหม่';

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
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-<?= $isEdit ? 'edit' : 'user-plus' ?>"></i>
                <?= $pageTitle ?>
            </h3>
        </div>
        <div class="card-body">
            <form id="employeeForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                
                <!-- Personal Info -->
                <h5 class="mb-3 text-primary"><i class="fas fa-user"></i> ข้อมูลส่วนบุคคล</h5>
                <div class="grid grid-cols-4 mb-4">
                    <div class="form-group">
                        <label class="form-label">คำนำหน้า</label>
                        <select name="prefix" class="form-control">
                            <option value="">-- เลือก --</option>
                            <option value="นาย" <?= ($employee['prefix'] ?? '') === 'นาย' ? 'selected' : '' ?>>นาย</option>
                            <option value="นาง" <?= ($employee['prefix'] ?? '') === 'นาง' ? 'selected' : '' ?>>นาง</option>
                            <option value="นางสาว" <?= ($employee['prefix'] ?? '') === 'นางสาว' ? 'selected' : '' ?>>นางสาว</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">ชื่อ</label>
                        <input type="text" name="first_name" class="form-control" required
                               value="<?= htmlspecialchars($employee['first_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label required">นามสกุล</label>
                        <input type="text" name="last_name" class="form-control" required
                               value="<?= htmlspecialchars($employee['last_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">ชื่อเล่น</label>
                        <input type="text" name="nickname" class="form-control"
                               value="<?= htmlspecialchars($employee['nickname'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">เลขบัตรประชาชน</label>
                        <input type="text" name="citizen_id" class="form-control" maxlength="13"
                               value="<?= htmlspecialchars($employee['citizen_id'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="grid grid-cols-4 mb-4">
                    <div class="form-group">
                        <label class="form-label">วันเกิด</label>
                        <input type="date" name="birth_date" class="form-control"
                               value="<?= $employee['birth_date'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">เพศ</label>
                        <select name="gender" class="form-control">
                            <option value="male" <?= ($employee['gender'] ?? 'male') === 'male' ? 'selected' : '' ?>>ชาย</option>
                            <option value="female" <?= ($employee['gender'] ?? '') === 'female' ? 'selected' : '' ?>>หญิง</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">หมู่เลือด</label>
                        <select name="blood_type" class="form-control">
                            <option value="">-- เลือก --</option>
                            <?php foreach (['A', 'B', 'AB', 'O'] as $type): ?>
                            <option value="<?= $type ?>" <?= ($employee['blood_type'] ?? '') === $type ? 'selected' : '' ?>><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ศาสนา</label>
                        <select name="religion" class="form-control">
                            <option value="">-- เลือก --</option>
                            <?php foreach (['พุทธ', 'อิสลาม', 'คริสต์', 'ฮินดู', 'อื่นๆ'] as $r): ?>
                            <option value="<?= $r ?>" <?= ($employee['religion'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-4 mb-4">
                    <div class="form-group">
                        <label class="form-label">สถานภาพ</label>
                        <select name="marital_status" class="form-control">
                            <option value="">-- เลือก --</option>
                            <?php foreach (['โสด', 'สมรส', 'หย่า', 'หม้าย'] as $m): ?>
                            <option value="<?= $m ?>" <?= ($employee['marital_status'] ?? '') === $m ? 'selected' : '' ?>><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ระดับการศึกษา</label>
                        <select name="education_level" class="form-control">
                            <option value="">-- เลือก --</option>
                            <?php foreach (['ประถมศึกษา', 'มัธยมศึกษา', 'ปวช.', 'ปวส.', 'ปริญญาตรี', 'ปริญญาโท', 'ปริญญาเอก'] as $e): ?>
                            <option value="<?= $e ?>" <?= ($employee['education_level'] ?? '') === $e ? 'selected' : '' ?>><?= $e ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">รูปภาพ</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                </div>
                
                <hr style="border-color: var(--border-color); margin: 2rem 0;">
                
                <!-- Contact Info -->
                <h5 class="mb-3 text-primary"><i class="fas fa-address-book"></i> ข้อมูลการติดต่อ</h5>
                <div class="grid grid-cols-3 mb-4">
                    <div class="form-group">
                        <label class="form-label">เบอร์โทรศัพท์</label>
                        <input type="tel" name="phone" class="form-control"
                               value="<?= htmlspecialchars($employee['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">อีเมล</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($employee['email'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-group mb-4">
                    <label class="form-label">ที่อยู่ปัจจุบัน</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($employee['address'] ?? '') ?></textarea>
                </div>
                
                <div class="grid grid-cols-3 mb-4">
                    <div class="form-group">
                        <label class="form-label">ชื่อผู้ติดต่อฉุกเฉิน</label>
                        <input type="text" name="emergency_contact_name" class="form-control"
                               value="<?= htmlspecialchars($employee['emergency_contact_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">ความสัมพันธ์</label>
                        <input type="text" name="emergency_contact_relation" class="form-control"
                               value="<?= htmlspecialchars($employee['emergency_contact_relation'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">เบอร์ติดต่อฉุกเฉิน</label>
                        <input type="tel" name="emergency_contact_phone" class="form-control"
                               value="<?= htmlspecialchars($employee['emergency_contact_phone'] ?? '') ?>">
                    </div>
                </div>
                
                <hr style="border-color: var(--border-color); margin: 2rem 0;">
                
                <!-- Work Info -->
                <h5 class="mb-3 text-primary"><i class="fas fa-briefcase"></i> ข้อมูลการทำงาน</h5>
                <div class="grid grid-cols-4 mb-4">
                    <div class="form-group">
                        <label class="form-label required">บริษัท</label>
                        <select name="company_id" id="companySelect" class="form-control" required onchange="loadBranches(this.value)">
                            <option value="">-- เลือก --</option>
                            <?php foreach ($companies as $company): ?>
                            <option value="<?= $company['company_id'] ?>" <?= ($employee['company_id'] ?? '') == $company['company_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($company['company_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">สาขา</label>
                        <select name="branch_id" id="branchSelect" class="form-control" required>
                            <option value="">-- เลือกบริษัทก่อน --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">แผนก</label>
                        <select name="department_id" id="departmentSelect" class="form-control">
                            <option value="">-- เลือก --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ตำแหน่ง</label>
                        <select name="position_id" class="form-control">
                            <option value="">-- เลือก --</option>
                            <?php foreach ($positions as $position): ?>
                            <option value="<?= $position['position_id'] ?>" <?= ($employee['position_id'] ?? '') == $position['position_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($position['position_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-4 mb-4">
                    <div class="form-group">
                        <label class="form-label">วันที่เริ่มงาน</label>
                        <input type="date" name="start_date" class="form-control"
                               value="<?= $employee['start_date'] ?? date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">ประเภท</label>
                        <select name="employee_type" class="form-control">
                            <option value="monthly" <?= ($employee['employee_type'] ?? 'monthly') === 'monthly' ? 'selected' : '' ?>>รายเดือน</option>
                            <option value="daily" <?= ($employee['employee_type'] ?? '') === 'daily' ? 'selected' : '' ?>>รายวัน</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">เงินเดือน/ค่าแรง</label>
                        <input type="number" name="salary" class="form-control" min="0" step="0.01"
                               value="<?= $employee['salary'] ?? '' ?>">
                    </div>
                    <?php if ($isEdit): ?>
                    <div class="form-group">
                        <label class="form-label">สถานะ</label>
                        <select name="status" class="form-control">
                            <option value="active" <?= ($employee['status'] ?? '') === 'active' ? 'selected' : '' ?>>ทำงานอยู่</option>
                            <option value="resigned" <?= ($employee['status'] ?? '') === 'resigned' ? 'selected' : '' ?>>ลาออก</option>
                            <option value="suspended" <?= ($employee['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>พักงาน</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-between">
                <a href="<?= BASE_URL ?>modules/employees/" class="btn btn-outline">
                    <i class="fas fa-times"></i> ยกเลิก
                </a>
                <button type="submit" form="employeeForm" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= $isEdit ? 'บันทึก' : 'เพิ่มพนักงาน' ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Load branches
function loadBranches(companyId, selectedBranchId = null) {
    const branchSelect = document.getElementById('branchSelect');
    branchSelect.innerHTML = '<option value="">-- กำลังโหลด --</option>';
    
    if (!companyId) {
        branchSelect.innerHTML = '<option value="">-- เลือกบริษัทก่อน --</option>';
        return;
    }
    
    fetch(`<?= BASE_URL ?>api/get_branches.php?company_id=${companyId}`)
        .then(response => response.json())
        .then(data => {
            branchSelect.innerHTML = '<option value="">-- เลือก --</option>';
            data.forEach(branch => {
                const option = document.createElement('option');
                option.value = branch.branch_id;
                option.textContent = branch.branch_name;
                if (selectedBranchId && branch.branch_id == selectedBranchId) {
                    option.selected = true;
                }
                branchSelect.appendChild(option);
            });
        });
}

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($isEdit && $employee['company_id']): ?>
    loadBranches('<?= $employee['company_id'] ?>', '<?= $employee['branch_id'] ?>');
    <?php endif; ?>
});

// Handle form submit
document.getElementById('employeeForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {};
    formData.forEach((value, key) => {
        if (value) data[key] = value;
    });
    
    showLoading('กำลังบันทึก...');
    
    try {
        const response = await fetch('<?= BASE_URL ?>api/employees.php', {
            method: '<?= $isEdit ? 'PUT' : 'POST' ?>',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            setTimeout(() => {
                window.location.href = '<?= BASE_URL ?>modules/employees/';
            }, 1500);
        } else {
            showError(result.error || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        showError('เกิดข้อผิดพลาดในการบันทึก');
        console.error(error);
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
