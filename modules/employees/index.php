<?php
/**
 * Employee List
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'ทะเบียนประวัติ';
$pdo = getDBConnection();

// Get filter values
$filterCompany = $_GET['company_id'] ?? '';
$filterBranch = $_GET['branch_id'] ?? '';
$filterStatus = $_GET['status'] ?? 'active';

// Get accessible companies
$accessibleCompanyIds = getAccessibleCompanyIds();
$placeholders = implode(',', array_fill(0, count($accessibleCompanyIds), '?'));

// Get companies for filter
$stmt = $pdo->prepare("
    SELECT company_id, company_name 
    FROM companies 
    WHERE status = 1 AND company_id IN ($placeholders)
    ORDER BY company_id
");
$stmt->execute($accessibleCompanyIds);
$companies = $stmt->fetchAll();

// Get branches for filter
$branches = [];
if ($filterCompany) {
    $stmt = $pdo->prepare("
        SELECT branch_id, branch_name 
        FROM branches 
        WHERE status = 1 AND company_id = ?
        ORDER BY branch_id
    ");
    $stmt->execute([$filterCompany]);
    $branches = $stmt->fetchAll();
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Content -->
<div class="page-content">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">ทะเบียนประวัติพนักงาน</h2>
            <p class="text-muted mb-0">จัดการข้อมูลพนักงานทั้งหมดในระบบ</p>
        </div>
        <?php if (isHR()): ?>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> เพิ่มพนักงาน
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="d-flex gap-3 flex-wrap align-items-end">
                <div class="form-group mb-0" style="min-width: 200px;">
                    <label class="form-label">บริษัท</label>
                    <select name="company_id" class="form-control" id="filterCompany" onchange="loadBranches(this.value)">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($companies as $company): ?>
                        <option value="<?= $company['company_id'] ?>" <?= $filterCompany == $company['company_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($company['company_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group mb-0" style="min-width: 180px;">
                    <label class="form-label">สาขา</label>
                    <select name="branch_id" class="form-control" id="filterBranch">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($branches as $branch): ?>
                        <option value="<?= $branch['branch_id'] ?>" <?= $filterBranch == $branch['branch_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($branch['branch_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group mb-0" style="min-width: 150px;">
                    <label class="form-label">สถานะ</label>
                    <select name="status" class="form-control">
                        <option value="">-- ทั้งหมด --</option>
                        <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>ทำงานอยู่</option>
                        <option value="resigned" <?= $filterStatus === 'resigned' ? 'selected' : '' ?>>ลาออก</option>
                        <option value="suspended" <?= $filterStatus === 'suspended' ? 'selected' : '' ?>>พักงาน</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> ค้นหา
                </button>
                
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-redo"></i> รีเซ็ต
                </a>
            </form>
        </div>
    </div>
    
    <!-- Employee Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table class="table" id="employeeTable">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>ชื่อเล่น</th>
                            <th>ตำแหน่ง</th>
                            <th>บริษัท</th>
                            <th>สาขา</th>
                            <th>ประเภท</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script>
// Load branches when company changes
function loadBranches(companyId) {
    const branchSelect = document.getElementById('filterBranch');
    branchSelect.innerHTML = '<option value="">-- ทั้งหมด --</option>';
    
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

// Initialize DataTable
$(document).ready(function() {
    const table = $('#employeeTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= BASE_URL ?>api/employees.php',
            data: function(d) {
                d.company_id = '<?= $filterCompany ?>';
                d.branch_id = '<?= $filterBranch ?>';
                d.status = '<?= $filterStatus ?>';
            }
        },
        columns: [
            { data: 'employee_code' },
            { 
                data: null,
                render: function(data) {
                    const avatar = data.photo 
                        ? `<img src="<?= BASE_URL ?>uploads/photos/${data.photo}" alt="">`
                        : data.first_name.charAt(0);
                    return `
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-sm avatar-primary">${avatar}</div>
                            <div>
                                <div class="fw-medium">${data.prefix}${data.first_name} ${data.last_name}</div>
                                <div class="text-muted fs-sm">${data.citizen_id || '-'}</div>
                            </div>
                        </div>
                    `;
                }
            },
            { data: 'nickname', defaultContent: '-' },
            { data: 'position_name', defaultContent: '-' },
            { data: 'company_name' },
            { data: 'branch_name' },
            { 
                data: 'employee_type',
                render: function(data) {
                    const types = {
                        'monthly': '<span class="badge badge-primary">รายเดือน</span>',
                        'daily': '<span class="badge badge-secondary">รายวัน</span>'
                    };
                    return types[data] || '-';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    return `
                        <div class="d-flex gap-2">
                            <a href="view.php?id=${data.employee_id}" class="btn btn-sm btn-outline" title="ดูข้อมูล">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (isHR()): ?>
                            <a href="edit.php?id=${data.employee_id}" class="btn btn-sm btn-outline" title="แก้ไข">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    `;
                }
            }
        ],
        language: {
            search: 'ค้นหา:',
            lengthMenu: 'แสดง _MENU_ รายการ',
            info: 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
            infoEmpty: 'ไม่พบรายการ',
            infoFiltered: '(กรองจาก _MAX_ รายการ)',
            zeroRecords: 'ไม่พบข้อมูลที่ค้นหา',
            paginate: {
                first: 'หน้าแรก',
                previous: 'ก่อนหน้า',
                next: 'ถัดไป',
                last: 'หน้าสุดท้าย'
            }
        },
        order: [[0, 'asc']]
    });
});
</script>

