<?php
/**
 * Leave Types Management
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$pageTitle = 'จัดการประเภทการลา';
$pdo = getDBConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $stmt = $pdo->prepare("
            INSERT INTO leave_types (leave_name, max_days_per_year, description)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $_POST['leave_name'],
            (int)$_POST['max_days_per_year'],
            $_POST['description'] ?? ''
        ]);
        header('Location: leave_types.php?msg=added');
        exit;
    }
    
    if ($action === 'edit') {
        $stmt = $pdo->prepare("
            UPDATE leave_types SET
                leave_name = ?,
                max_days_per_year = ?,
                description = ?,
                updated_at = NOW()
            WHERE leave_type_id = ?
        ");
        $stmt->execute([
            $_POST['leave_name'],
            (int)$_POST['max_days_per_year'],
            $_POST['description'] ?? '',
            (int)$_POST['leave_type_id']
        ]);
        header('Location: leave_types.php?msg=updated');
        exit;
    }
    
    if ($action === 'delete') {
        $stmt = $pdo->prepare("UPDATE leave_types SET status = 0 WHERE leave_type_id = ?");
        $stmt->execute([(int)$_POST['leave_type_id']]);
        header('Location: leave_types.php?msg=deleted');
        exit;
    }
}

// Get leave types
$stmt = $pdo->query("SELECT * FROM leave_types WHERE status = 1 ORDER BY leave_type_id");
$leaveTypes = $stmt->fetchAll();

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
        <?= $msg === 'added' ? 'เพิ่มประเภทการลาเรียบร้อย' : ($msg === 'updated' ? 'แก้ไขเรียบร้อย' : 'ลบเรียบร้อย') ?>
    </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>จัดการประเภทการลา</h2>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> เพิ่มประเภท
        </button>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>ชื่อประเภท</th>
                            <th style="width: 150px;">สิทธิ์/ปี (วัน)</th>
                            <th>คำอธิบาย</th>
                            <th style="width: 120px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaveTypes as $index => $type): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= htmlspecialchars($type['leave_name']) ?></strong></td>
                            <td>
                                <span class="badge badge-primary"><?= $type['max_days_per_year'] ?> วัน</span>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($type['description'] ?? '-') ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline" 
                                            onclick="showEditModal(<?= htmlspecialchars(json_encode($type)) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="deleteType(<?= $type['leave_type_id'] ?>)">
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
<div class="modal-overlay" id="leaveTypeModal">
    <div class="modal">
        <div class="modal-header">
            <h4 class="modal-title" id="modalTitle">เพิ่มประเภทการลา</h4>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="leave_type_id" id="leaveTypeId">
                
                <div class="form-group">
                    <label class="form-label required">ชื่อประเภทการลา</label>
                    <input type="text" name="leave_name" id="leaveName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">สิทธิ์ต่อปี (วัน)</label>
                    <input type="number" name="max_days_per_year" id="maxDays" class="form-control" min="0" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">คำอธิบาย</label>
                    <textarea name="description" id="description" class="form-control" rows="2"></textarea>
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
    <input type="hidden" name="leave_type_id" id="deleteId">
</form>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'เพิ่มประเภทการลา';
    document.getElementById('formAction').value = 'add';
    document.getElementById('leaveTypeId').value = '';
    document.getElementById('leaveName').value = '';
    document.getElementById('maxDays').value = '';
    document.getElementById('description').value = '';
    document.getElementById('leaveTypeModal').classList.add('active');
}

function showEditModal(data) {
    document.getElementById('modalTitle').textContent = 'แก้ไขประเภทการลา';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('leaveTypeId').value = data.leave_type_id;
    document.getElementById('leaveName').value = data.leave_name;
    document.getElementById('maxDays').value = data.max_days_per_year;
    document.getElementById('description').value = data.description || '';
    document.getElementById('leaveTypeModal').classList.add('active');
}

function closeModal() {
    document.getElementById('leaveTypeModal').classList.remove('active');
}

function deleteType(id) {
    confirmDelete(() => {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
