<?php
/**
 * Positions Management
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$pageTitle = 'จัดการตำแหน่งงาน';
$pdo = getDBConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $stmt = $pdo->prepare("
            INSERT INTO positions (position_name, base_salary, description)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $_POST['position_name'],
            (float)$_POST['base_salary'],
            $_POST['description'] ?? ''
        ]);
        header('Location: positions.php?msg=added');
        exit;
    }
    
    if ($action === 'edit') {
        $stmt = $pdo->prepare("
            UPDATE positions SET
                position_name = ?,
                base_salary = ?,
                description = ?,
                updated_at = NOW()
            WHERE position_id = ?
        ");
        $stmt->execute([
            $_POST['position_name'],
            (float)$_POST['base_salary'],
            $_POST['description'] ?? '',
            (int)$_POST['position_id']
        ]);
        header('Location: positions.php?msg=updated');
        exit;
    }
    
    if ($action === 'delete') {
        $stmt = $pdo->prepare("UPDATE positions SET status = 0 WHERE position_id = ?");
        $stmt->execute([(int)$_POST['position_id']]);
        header('Location: positions.php?msg=deleted');
        exit;
    }
}

// Get positions
$stmt = $pdo->query("
    SELECT p.*,
           (SELECT COUNT(*) FROM employees WHERE position_id = p.position_id AND status = 'active') as employee_count
    FROM positions p 
    WHERE p.status = 1 
    ORDER BY p.position_id
");
$positions = $stmt->fetchAll();

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
        <?= $msg === 'added' ? 'เพิ่มตำแหน่งเรียบร้อย' : ($msg === 'updated' ? 'แก้ไขเรียบร้อย' : 'ลบเรียบร้อย') ?>
    </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>จัดการตำแหน่งงาน</h2>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> เพิ่มตำแหน่ง
        </button>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>ชื่อตำแหน่ง</th>
                            <th style="width: 150px;">เงินเดือนเริ่มต้น</th>
                            <th>คำอธิบาย</th>
                            <th style="width: 100px;">พนักงาน</th>
                            <th style="width: 120px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($positions as $index => $position): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= htmlspecialchars($position['position_name']) ?></strong></td>
                            <td class="text-success fw-bold"><?= number_format($position['base_salary'], 0) ?> ฿</td>
                            <td class="text-muted"><?= htmlspecialchars($position['description'] ?? '-') ?></td>
                            <td><span class="badge badge-accent"><?= $position['employee_count'] ?> คน</span></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline" 
                                            onclick="showEditModal(<?= htmlspecialchars(json_encode($position)) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="deletePosition(<?= $position['position_id'] ?>)"
                                            <?= $position['employee_count'] > 0 ? 'disabled title="ไม่สามารถลบได้ มีพนักงานอยู่"' : '' ?>>
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
<div class="modal-overlay" id="positionModal">
    <div class="modal">
        <div class="modal-header">
            <h4 class="modal-title" id="modalTitle">เพิ่มตำแหน่ง</h4>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="position_id" id="positionId">
                
                <div class="form-group">
                    <label class="form-label required">ชื่อตำแหน่ง</label>
                    <input type="text" name="position_name" id="positionName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">เงินเดือนเริ่มต้น (บาท)</label>
                    <input type="number" name="base_salary" id="baseSalary" class="form-control" min="0" step="100">
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
    <input type="hidden" name="position_id" id="deleteId">
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'เพิ่มตำแหน่ง';
    document.getElementById('formAction').value = 'add';
    document.getElementById('positionId').value = '';
    document.getElementById('positionName').value = '';
    document.getElementById('baseSalary').value = '';
    document.getElementById('description').value = '';
    document.getElementById('positionModal').classList.add('active');
}

function showEditModal(data) {
    document.getElementById('modalTitle').textContent = 'แก้ไขตำแหน่ง';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('positionId').value = data.position_id;
    document.getElementById('positionName').value = data.position_name;
    document.getElementById('baseSalary').value = data.base_salary || '';
    document.getElementById('description').value = data.description || '';
    document.getElementById('positionModal').classList.add('active');
}

function closeModal() {
    document.getElementById('positionModal').classList.remove('active');
}

function deletePosition(id) {
    confirmDelete(() => {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    });
}
</script>
