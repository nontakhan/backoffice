<?php
/**
 * Leave Approval Page
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'hr', 'supervisor']);

$pageTitle = 'อนุมัติลา';
$pdo = getDBConnection();

$role = getRole();
$approvalMode = 'supervisor';
if (isHR() || isAdmin()) {
    $approvalMode = $_GET['mode'] ?? 'hr';
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Content -->
<div class="page-content">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">อนุมัติการลา</h2>
            <p class="text-muted mb-0">รายการใบลาที่รอการอนุมัติ</p>
        </div>
        
        <?php if (isHR()): ?>
        <div class="d-flex gap-2">
            <a href="?mode=supervisor" class="btn <?= $approvalMode === 'supervisor' ? 'btn-primary' : 'btn-outline' ?>">
                รอหัวหน้าอนุมัติ
            </a>
            <a href="?mode=hr" class="btn <?= $approvalMode === 'hr' ? 'btn-primary' : 'btn-outline' ?>">
                รอ HR อนุมัติ
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Pending Leaves -->
    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table class="table" id="approvalTable">
                    <thead>
                        <tr>
                            <th>วันที่ยื่น</th>
                            <th>พนักงาน</th>
                            <th>ประเภท</th>
                            <th>วันที่ลา</th>
                            <th>จำนวนวัน</th>
                            <th>เหตุผล</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal-overlay" id="approvalModal">
    <div class="modal">
        <div class="modal-header">
            <h4 class="modal-title" id="modalTitle">อนุมัติการลา</h4>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="leaveIdInput">
            <input type="hidden" id="actionInput">
            
            <div class="form-group">
                <label class="form-label">ความคิดเห็น</label>
                <textarea id="commentInput" class="form-control" rows="3" placeholder="ความคิดเห็น (ถ้ามี)"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal()">ยกเลิก</button>
            <button class="btn btn-primary" onclick="submitApproval()">ยืนยัน</button>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#approvalTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= BASE_URL ?>api/leaves.php',
            data: function(d) {
                d.mode = 'pending';
                d.approval_mode = '<?= $approvalMode ?>';
            }
        },
        columns: [
            { 
                data: 'created_at',
                render: data => formatThaiDate(data)
            },
            { 
                data: null,
                render: data => `
                    <div>
                        <strong>${data.prefix || ''}${data.first_name} ${data.last_name}</strong>
                        <br><small class="text-muted">${data.branch_name}</small>
                    </div>
                `
            },
            { data: 'leave_name' },
            { 
                data: null,
                render: data => {
                    if (data.start_date === data.end_date) {
                        return formatThaiDate(data.start_date);
                    }
                    return `${formatThaiDate(data.start_date)}<br>- ${formatThaiDate(data.end_date)}`;
                }
            },
            { data: 'total_days' },
            { 
                data: 'reason',
                render: data => data ? (data.length > 50 ? data.substring(0, 50) + '...' : data) : '-'
            },
            {
                data: null,
                orderable: false,
                render: data => `
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-success" onclick="openApproval(${data.leave_id}, 'approve')" title="อนุมัติ">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="openApproval(${data.leave_id}, 'reject')" title="ไม่อนุมัติ">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `
            }
        ],
        language: {
            search: 'ค้นหา:',
            lengthMenu: 'แสดง _MENU_ รายการ',
            info: 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
            infoEmpty: 'ไม่พบรายการ',
            zeroRecords: 'ไม่มีใบลาที่รออนุมัติ',
            paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' }
        },
        order: [[0, 'asc']]
    });
});

function openApproval(leaveId, action) {
    document.getElementById('leaveIdInput').value = leaveId;
    document.getElementById('actionInput').value = action;
    document.getElementById('modalTitle').textContent = action === 'approve' ? 'อนุมัติการลา' : 'ไม่อนุมัติการลา';
    document.getElementById('commentInput').value = '';
    document.getElementById('approvalModal').classList.add('active');
}

function closeModal() {
    document.getElementById('approvalModal').classList.remove('active');
}

async function submitApproval() {
    const leaveId = document.getElementById('leaveIdInput').value;
    const action = document.getElementById('actionInput').value;
    const comment = document.getElementById('commentInput').value;
    
    showLoading('กำลังดำเนินการ...');
    
    try {
        const response = await fetch('<?= BASE_URL ?>api/leaves.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                leave_id: leaveId,
                action: action,
                approval_mode: '<?= $approvalMode ?>',
                comment: comment
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeModal();
            showSuccess(result.message);
            $('#approvalTable').DataTable().ajax.reload();
        } else {
            showError(result.error || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        showError('เกิดข้อผิดพลาด');
        console.error(error);
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
