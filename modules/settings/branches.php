<?php
/**
 * Branches Management
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$pageTitle = 'จัดการสาขา';
$pdo = getDBConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $stmt = $pdo->prepare("
            INSERT INTO branches (company_id, branch_code, branch_name, address, phone)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            (int)$_POST['company_id'],
            $_POST['branch_code'],
            $_POST['branch_name'],
            $_POST['address'] ?? '',
            $_POST['phone'] ?? ''
        ]);
        header('Location: branches.php?msg=added');
        exit;
    }
    
    if ($action === 'edit') {
        $stmt = $pdo->prepare("
            UPDATE branches SET
                company_id = ?,
                branch_code = ?,
                branch_name = ?,
                address = ?,
                phone = ?,
                updated_at = NOW()
            WHERE branch_id = ?
        ");
        $stmt->execute([
            (int)$_POST['company_id'],
            $_POST['branch_code'],
            $_POST['branch_name'],
            $_POST['address'] ?? '',
            $_POST['phone'] ?? '',
            (int)$_POST['branch_id']
        ]);
        header('Location: branches.php?msg=updated');
        exit;
    }
    
    if ($action === 'delete') {
        $stmt = $pdo->prepare("UPDATE branches SET status = 0 WHERE branch_id = ?");
        $stmt->execute([(int)$_POST['branch_id']]);
        header('Location: branches.php?msg=deleted');
        exit;
    }
}

// Get companies for dropdown
$stmt = $pdo->query("SELECT company_id, company_name FROM companies WHERE status = 1 ORDER BY company_id");
$companies = $stmt->fetchAll();

// Get branches
$stmt = $pdo->query("
    SELECT b.*, c.company_name,
           (SELECT COUNT(*) FROM employees WHERE branch_id = b.branch_id AND status = 'active') as employee_count
    FROM branches b 
    JOIN companies c ON b.company_id = c.company_id
    WHERE b.status = 1 
    ORDER BY b.company_id, b.branch_id
");
$branches = $stmt->fetchAll();

$msg = $_GET['msg'] ?? '';

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Content -->
<div class="page-content">
    <!-- Breadcrumb -->
    <nav class="mb-4">
        <a href="<?= BASE_URL ?>modules/settings/" class="text-muted">
            <i class="fas fa-arrow-left"></i> กลับไปตั้งค่า
        </a>
    </nav>
    
    <?php if ($msg): ?>
    <div class="alert alert-success mb-4">
        <i class="fas fa-check-circle alert-icon"></i>
        <?= $msg === 'added' ? 'เพิ่มสาขาเรียบร้อย' : ($msg === 'updated' ? 'แก้ไขเรียบร้อย' : 'ลบเรียบร้อย') ?>
    </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>จัดการสาขา</h2>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> เพิ่มสาขา
        </button>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 100px;">รหัส</th>
                            <th>ชื่อสาขา</th>
                            <th>บริษัท</th>
                            <th>ที่อยู่</th>
                            <th style="width: 100px;">พนักงาน</th>
                            <th style="width: 120px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($branches as $branch): ?>
                        <tr>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($branch['branch_code']) ?></span></td>
                            <td><strong><?= htmlspecialchars($branch['branch_name']) ?></strong></td>
                            <td><?= htmlspecialchars($branch['company_name']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($branch['address'] ?? '-') ?></td>
                            <td><span class="badge badge-accent"><?= $branch['employee_count'] ?> คน</span></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline" 
                                            onclick="showEditModal(<?= htmlspecialchars(json_encode($branch)) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="deleteBranch(<?= $branch['branch_id'] ?>)"
                                            <?= $branch['employee_count'] > 0 ? 'disabled title="ไม่สามารถลบได้ มีพนักงานอยู่"' : '' ?>>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="branchModal">
    <div class="modal">
        <div class="modal-header">
            <h4 class="modal-title" id="modalTitle">เพิ่มสาขา</h4>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="branch_id" id="branchId">
                
                <div class="form-group">
                    <label class="form-label required">บริษัท</label>
                    <select name="company_id" id="companyId" class="form-control" required>
                        <option value="">-- เลือกบริษัท --</option>
                        <?php foreach ($companies as $company): ?>
                        <option value="<?= $company['company_id'] ?>"><?= htmlspecialchars($company['company_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2">
                    <div class="form-group">
                        <label class="form-label required">รหัสสาขา</label>
                        <input type="text" name="branch_code" id="branchCode" class="form-control" required maxlength="20">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">ชื่อสาขา</label>
                        <input type="text" name="branch_name" id="branchName" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">ที่อยู่</label>
                    <textarea name="address" id="address" class="form-control" rows="2"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">โทรศัพท์</label>
                    <input type="text" name="phone" id="phone" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal()">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form method="POST" id="deleteForm" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="branch_id" id="deleteId">
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'เพิ่มสาขา';
    document.getElementById('formAction').value = 'add';
    document.getElementById('branchId').value = '';
    document.getElementById('companyId').value = '';
    document.getElementById('branchCode').value = '';
    document.getElementById('branchName').value = '';
    document.getElementById('address').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('branchModal').classList.add('active');
}

function showEditModal(data) {
    document.getElementById('modalTitle').textContent = 'แก้ไขสาขา';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('branchId').value = data.branch_id;
    document.getElementById('companyId').value = data.company_id;
    document.getElementById('branchCode').value = data.branch_code;
    document.getElementById('branchName').value = data.branch_name;
    document.getElementById('address').value = data.address || '';
    document.getElementById('phone').value = data.phone || '';
    document.getElementById('branchModal').classList.add('active');
}

function closeModal() {
    document.getElementById('branchModal').classList.remove('active');
}

function deleteBranch(id) {
    confirmDelete(() => {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    });
}
</script>
