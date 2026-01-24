<?php
/**
 * Companies Management
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$pageTitle = 'จัดการบริษัท';
$pdo = getDBConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $stmt = $pdo->prepare("
            INSERT INTO companies (company_code, company_name, tax_id, address, phone)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['company_code'],
            $_POST['company_name'],
            $_POST['tax_id'] ?? '',
            $_POST['address'] ?? '',
            $_POST['phone'] ?? ''
        ]);
        header('Location: companies.php?msg=added');
        exit;
    }
    
    if ($action === 'edit') {
        $stmt = $pdo->prepare("
            UPDATE companies SET
                company_code = ?,
                company_name = ?,
                tax_id = ?,
                address = ?,
                phone = ?,
                updated_at = NOW()
            WHERE company_id = ?
        ");
        $stmt->execute([
            $_POST['company_code'],
            $_POST['company_name'],
            $_POST['tax_id'] ?? '',
            $_POST['address'] ?? '',
            $_POST['phone'] ?? '',
            (int)$_POST['company_id']
        ]);
        header('Location: companies.php?msg=updated');
        exit;
    }
    
    if ($action === 'delete') {
        $stmt = $pdo->prepare("UPDATE companies SET status = 0 WHERE company_id = ?");
        $stmt->execute([(int)$_POST['company_id']]);
        header('Location: companies.php?msg=deleted');
        exit;
    }
}

// Get companies
$stmt = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM branches WHERE company_id = c.company_id AND status = 1) as branch_count,
           (SELECT COUNT(*) FROM employees WHERE company_id = c.company_id AND status = 'active') as employee_count
    FROM companies c 
    WHERE c.status = 1 
    ORDER BY c.company_id
");
$companies = $stmt->fetchAll();

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
        <?= $msg === 'added' ? 'เพิ่มบริษัทเรียบร้อย' : ($msg === 'updated' ? 'แก้ไขเรียบร้อย' : 'ลบเรียบร้อย') ?>
    </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>จัดการบริษัท</h2>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> เพิ่มบริษัท
        </button>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 100px;">รหัส</th>
                            <th>ชื่อบริษัท</th>
                            <th style="width: 140px;">เลขประจำตัวผู้เสียภาษี</th>
                            <th style="width: 100px;">โทรศัพท์</th>
                            <th style="width: 80px;">สาขา</th>
                            <th style="width: 100px;">พนักงาน</th>
                            <th style="width: 120px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><span class="badge badge-primary"><?= htmlspecialchars($company['company_code']) ?></span></td>
                            <td><strong><?= htmlspecialchars($company['company_name']) ?></strong></td>
                            <td class="text-muted"><?= htmlspecialchars($company['tax_id'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($company['phone'] ?? '-') ?></td>
                            <td><span class="badge badge-secondary"><?= $company['branch_count'] ?></span></td>
                            <td><span class="badge badge-accent"><?= $company['employee_count'] ?> คน</span></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline" 
                                            onclick="showEditModal(<?= htmlspecialchars(json_encode($company)) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="deleteCompany(<?= $company['company_id'] ?>)"
                                            <?= $company['employee_count'] > 0 ? 'disabled title="ไม่สามารถลบได้ มีพนักงานอยู่"' : '' ?>>
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
<div class="modal-overlay" id="companyModal">
    <div class="modal">
        <div class="modal-header">
            <h4 class="modal-title" id="modalTitle">เพิ่มบริษัท</h4>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="company_id" id="companyId">
                
                <div class="grid grid-cols-2">
                    <div class="form-group">
                        <label class="form-label required">รหัสบริษัท</label>
                        <input type="text" name="company_code" id="companyCode" class="form-control" required maxlength="10">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">เลขประจำตัวผู้เสียภาษี</label>
                        <input type="text" name="tax_id" id="taxId" class="form-control" maxlength="20">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">ชื่อบริษัท</label>
                    <input type="text" name="company_name" id="companyName" class="form-control" required>
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
    <input type="hidden" name="company_id" id="deleteId">
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'เพิ่มบริษัท';
    document.getElementById('formAction').value = 'add';
    document.getElementById('companyId').value = '';
    document.getElementById('companyCode').value = '';
    document.getElementById('companyName').value = '';
    document.getElementById('taxId').value = '';
    document.getElementById('address').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('companyModal').classList.add('active');
}

function showEditModal(data) {
    document.getElementById('modalTitle').textContent = 'แก้ไขบริษัท';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('companyId').value = data.company_id;
    document.getElementById('companyCode').value = data.company_code;
    document.getElementById('companyName').value = data.company_name;
    document.getElementById('taxId').value = data.tax_id || '';
    document.getElementById('address').value = data.address || '';
    document.getElementById('phone').value = data.phone || '';
    document.getElementById('companyModal').classList.add('active');
}

function closeModal() {
    document.getElementById('companyModal').classList.remove('active');
}

function deleteCompany(id) {
    confirmDelete(() => {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    });
}
</script>
