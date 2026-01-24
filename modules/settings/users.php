<?php
/**
 * Users Management
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$pageTitle = 'จัดการผู้ใช้ระบบ';
$pdo = getDBConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$_POST['username']]);
        if ($stmt->fetch()) {
            header('Location: users.php?msg=exists');
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, role, employee_id, company_id, branch_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['username'],
            password_hash($_POST['password'], PASSWORD_DEFAULT),
            $_POST['role'],
            !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null,
            !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null,
            !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null
        ]);
        header('Location: users.php?msg=added');
        exit;
    }
    
    if ($action === 'edit') {
        $updateFields = "username = ?, role = ?, employee_id = ?, company_id = ?, branch_id = ?";
        $params = [
            $_POST['username'],
            $_POST['role'],
            !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null,
            !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null,
            !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null
        ];
        
        // Update password if provided
        if (!empty($_POST['password'])) {
            $updateFields .= ", password = ?";
            $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        $params[] = (int)$_POST['user_id'];
        
        $stmt = $pdo->prepare("UPDATE users SET $updateFields, updated_at = NOW() WHERE user_id = ?");
        $stmt->execute($params);
        header('Location: users.php?msg=updated');
        exit;
    }
    
    if ($action === 'delete') {
        $stmt = $pdo->prepare("UPDATE users SET status = 0 WHERE user_id = ?");
        $stmt->execute([(int)$_POST['user_id']]);
        header('Location: users.php?msg=deleted');
        exit;
    }
}

// Get companies for dropdown
$stmt = $pdo->query("SELECT company_id, company_name FROM companies WHERE status = 1 ORDER BY company_id");
$companies = $stmt->fetchAll();

// Get users
$stmt = $pdo->query("
    SELECT u.*, c.company_name, b.branch_name,
           CONCAT(e.prefix, e.first_name, ' ', e.last_name) as employee_name
    FROM users u 
    LEFT JOIN companies c ON u.company_id = c.company_id
    LEFT JOIN branches b ON u.branch_id = b.branch_id
    LEFT JOIN employees e ON u.employee_id = e.employee_id
    WHERE u.status = 1 
    ORDER BY u.user_id
");
$users = $stmt->fetchAll();

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
    <div class="alert alert-<?= $msg === 'exists' ? 'danger' : 'success' ?> mb-4">
        <i class="fas fa-<?= $msg === 'exists' ? 'exclamation-circle' : 'check-circle' ?> alert-icon"></i>
        <?php
        switch ($msg) {
            case 'added': echo 'เพิ่มผู้ใช้เรียบร้อย'; break;
            case 'updated': echo 'แก้ไขเรียบร้อย'; break;
            case 'deleted': echo 'ลบเรียบร้อย'; break;
            case 'exists': echo 'ชื่อผู้ใช้นี้มีอยู่แล้ว'; break;
        }
        ?>
    </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>จัดการผู้ใช้ระบบ</h2>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> เพิ่มผู้ใช้
        </button>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>ชื่อผู้ใช้</th>
                            <th>ชื่อพนักงาน</th>
                            <th>สิทธิ์</th>
                            <th>บริษัท</th>
                            <th>สาขา</th>
                            <th style="width: 150px;">เข้าสู่ระบบล่าสุด</th>
                            <th style="width: 120px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $index => $user): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                            <td><?= htmlspecialchars($user['employee_name'] ?? '-') ?></td>
                            <td>
                                <?php
                                $roleColors = [
                                    'admin' => 'badge-danger',
                                    'hr' => 'badge-primary',
                                    'supervisor' => 'badge-secondary',
                                    'employee' => 'badge-accent'
                                ];
                                $roleNames = [
                                    'admin' => 'Admin',
                                    'hr' => 'HR',
                                    'supervisor' => 'หัวหน้า',
                                    'employee' => 'พนักงาน'
                                ];
                                ?>
                                <span class="badge <?= $roleColors[$user['role']] ?? 'badge-secondary' ?>">
                                    <?= $roleNames[$user['role']] ?? $user['role'] ?>
                                </span>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($user['company_name'] ?? '-') ?></td>
                            <td class="text-muted"><?= htmlspecialchars($user['branch_name'] ?? '-') ?></td>
                            <td class="text-muted fs-sm">
                                <?= $user['last_login'] ? formatThaiDate($user['last_login']) : 'ยังไม่เคยเข้าใช้' ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline" 
                                            onclick="showEditModal(<?= htmlspecialchars(json_encode($user)) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="deleteUser(<?= $user['user_id'] ?>)"
                                            <?= $user['user_id'] == 1 ? 'disabled title="ไม่สามารถลบ admin หลักได้"' : '' ?>>
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
<div class="modal-overlay" id="userModal">
    <div class="modal">
        <div class="modal-header">
            <h4 class="modal-title" id="modalTitle">เพิ่มผู้ใช้</h4>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="user_id" id="userId">
                
                <div class="grid grid-cols-2">
                    <div class="form-group">
                        <label class="form-label required">ชื่อผู้ใช้</label>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" id="passwordLabel">รหัสผ่าน</label>
                        <input type="password" name="password" id="password" class="form-control">
                        <small class="text-muted" id="passwordHint" style="display: none;">เว้นว่างหากไม่ต้องการเปลี่ยน</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">สิทธิ์</label>
                    <select name="role" id="role" class="form-control" required>
                        <option value="admin">Admin</option>
                        <option value="hr">HR</option>
                        <option value="supervisor">หัวหน้า</option>
                        <option value="employee">พนักงาน</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-2">
                    <div class="form-group">
                        <label class="form-label">บริษัท</label>
                        <select name="company_id" id="companyId" class="form-control" onchange="loadBranches(this.value)">
                            <option value="">-- ทุกบริษัท (Admin) --</option>
                            <?php foreach ($companies as $company): ?>
                            <option value="<?= $company['company_id'] ?>"><?= htmlspecialchars($company['company_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">สาขา</label>
                        <select name="branch_id" id="branchId" class="form-control">
                            <option value="">-- ทุกสาขา --</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">เชื่อมกับพนักงาน (ไม่บังคับ)</label>
                    <input type="number" name="employee_id" id="employeeId" class="form-control" placeholder="รหัสพนักงาน">
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
    <input type="hidden" name="user_id" id="deleteId">
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script>
function loadBranches(companyId) {
    const branchSelect = document.getElementById('branchId');
    branchSelect.innerHTML = '<option value="">-- ทุกสาขา --</option>';
    
    if (!companyId) return;
    
    fetch(`<?= BASE_URL ?>api/get_branches.php?company_id=${companyId}`)
        .then(response => response.json())
        .then(data => {
            data.forEach(branch => {
                const option = document.createElement('option');
                option.value = branch.branch_id;
                option.textContent = branch.branch_name;
                branchSelect.appendChild(option);
            });
        });
}

function showAddModal() {
    document.getElementById('modalTitle').textContent = 'เพิ่มผู้ใช้';
    document.getElementById('formAction').value = 'add';
    document.getElementById('userId').value = '';
    document.getElementById('username').value = '';
    document.getElementById('password').value = '';
    document.getElementById('password').required = true;
    document.getElementById('passwordLabel').classList.add('required');
    document.getElementById('passwordHint').style.display = 'none';
    document.getElementById('role').value = 'employee';
    document.getElementById('companyId').value = '';
    document.getElementById('branchId').innerHTML = '<option value="">-- ทุกสาขา --</option>';
    document.getElementById('employeeId').value = '';
    document.getElementById('userModal').classList.add('active');
}

function showEditModal(data) {
    document.getElementById('modalTitle').textContent = 'แก้ไขผู้ใช้';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('userId').value = data.user_id;
    document.getElementById('username').value = data.username;
    document.getElementById('password').value = '';
    document.getElementById('password').required = false;
    document.getElementById('passwordLabel').classList.remove('required');
    document.getElementById('passwordHint').style.display = 'block';
    document.getElementById('role').value = data.role;
    document.getElementById('companyId').value = data.company_id || '';
    document.getElementById('employeeId').value = data.employee_id || '';
    
    // Load branches if company is set
    if (data.company_id) {
        loadBranches(data.company_id);
        setTimeout(() => {
            document.getElementById('branchId').value = data.branch_id || '';
        }, 300);
    }
    
    document.getElementById('userModal').classList.add('active');
}

function closeModal() {
    document.getElementById('userModal').classList.remove('active');
}

function deleteUser(id) {
    confirmDelete(() => {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    });
}
</script>
