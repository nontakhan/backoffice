<?php
/**
 * Leave Request List
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'รายการลา';
$pdo = getDBConnection();

// Get leave types for filter
$stmt = $pdo->query("SELECT leave_type_id, leave_name FROM leave_types WHERE status = 1");
$leaveTypes = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Content -->
<div class="page-content">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">รายการลา</h2>
            <p class="text-muted mb-0">ประวัติการลาทั้งหมด</p>
        </div>
        <a href="request.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> ยื่นใบลา
        </a>
    </div>
    
    <!-- Leave Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table class="table" id="leaveTable">
                    <thead>
                        <tr>
                            <th>วันที่ยื่น</th>
                            <th>พนักงาน</th>
                            <th>ประเภท</th>
                            <th>วันที่ลา</th>
                            <th>จำนวนวัน</th>
                            <th>หัวหน้า</th>
                            <th>HR</th>
                            <th>สถานะ</th>
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

<script>
$(document).ready(function() {
    $('#leaveTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= BASE_URL ?>api/leaves.php',
            data: function(d) {
                d.mode = 'list';
            }
        },
        columns: [
            { 
                data: 'created_at',
                render: function(data) {
                    return formatThaiDate(data);
                }
            },
            { 
                data: null,
                render: function(data) {
                    return `${data.prefix || ''}${data.first_name} ${data.last_name}`;
                }
            },
            { data: 'leave_name' },
            { 
                data: null,
                render: function(data) {
                    const start = formatThaiDate(data.start_date);
                    const end = formatThaiDate(data.end_date);
                    if (data.start_date === data.end_date) {
                        return start;
                    }
                    return `${start} - ${end}`;
                }
            },
            { data: 'total_days' },
            { 
                data: 'supervisor_status',
                render: function(data) {
                    const statuses = {
                        'pending': '<span class="badge badge-warning">รอ</span>',
                        'approved': '<span class="badge badge-success">อนุมัติ</span>',
                        'rejected': '<span class="badge badge-danger">ไม่อนุมัติ</span>'
                    };
                    return statuses[data] || '-';
                }
            },
            { 
                data: 'hr_status',
                render: function(data) {
                    const statuses = {
                        'pending': '<span class="badge badge-warning">รอ</span>',
                        'approved': '<span class="badge badge-success">อนุมัติ</span>',
                        'rejected': '<span class="badge badge-danger">ไม่อนุมัติ</span>'
                    };
                    return statuses[data] || '-';
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    const statuses = {
                        'pending': '<span class="badge badge-warning">รออนุมัติ</span>',
                        'approved': '<span class="badge badge-success">อนุมัติแล้ว</span>',
                        'rejected': '<span class="badge badge-danger">ไม่อนุมัติ</span>',
                        'cancelled': '<span class="badge badge-secondary">ยกเลิก</span>'
                    };
                    return statuses[data] || '-';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    let actions = `<button class="btn btn-sm btn-outline" onclick="viewLeave(${data.leave_id})" title="ดูรายละเอียด">
                        <i class="fas fa-eye"></i>
                    </button>`;
                    
                    if (data.status === 'pending' && data.can_cancel) {
                        actions += ` <button class="btn btn-sm btn-danger" onclick="cancelLeave(${data.leave_id})" title="ยกเลิก">
                            <i class="fas fa-times"></i>
                        </button>`;
                    }
                    
                    return `<div class="d-flex gap-2">${actions}</div>`;
                }
            }
        ],
        language: {
            search: 'ค้นหา:',
            lengthMenu: 'แสดง _MENU_ รายการ',
            info: 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
            infoEmpty: 'ไม่พบรายการ',
            zeroRecords: 'ไม่พบข้อมูลที่ค้นหา',
            paginate: {
                first: 'หน้าแรก',
                previous: 'ก่อนหน้า',
                next: 'ถัดไป',
                last: 'หน้าสุดท้าย'
            }
        },
        order: [[0, 'desc']]
    });
});

function viewLeave(id) {
    // Show leave details modal
    SwalCustom.fire({
        title: 'รายละเอียดการลา',
        html: '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</div>',
        showConfirmButton: false,
        didOpen: async () => {
            try {
                const response = await fetch(`<?= BASE_URL ?>api/leaves.php?id=${id}`);
                const data = await response.json();
                
                Swal.update({
                    html: `
                        <div class="text-left">
                            <p><strong>พนักงาน:</strong> ${data.prefix || ''}${data.first_name} ${data.last_name}</p>
                            <p><strong>ประเภท:</strong> ${data.leave_name}</p>
                            <p><strong>วันที่:</strong> ${formatThaiDate(data.start_date)} - ${formatThaiDate(data.end_date)}</p>
                            <p><strong>จำนวนวัน:</strong> ${data.total_days}</p>
                            <p><strong>เหตุผล:</strong> ${data.reason || '-'}</p>
                        </div>
                    `,
                    showConfirmButton: true,
                    confirmButtonText: 'ปิด'
                });
            } catch (error) {
                Swal.update({
                    html: '<p class="text-danger">ไม่สามารถโหลดข้อมูลได้</p>',
                    showConfirmButton: true,
                    confirmButtonText: 'ปิด'
                });
            }
        }
    });
}

function cancelLeave(id) {
    confirmDialog('ยกเลิกใบลา?', 'คุณต้องการยกเลิกใบลานี้หรือไม่?', async () => {
        try {
            const response = await fetch(`<?= BASE_URL ?>api/leaves.php`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ leave_id: id })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showSuccess('ยกเลิกใบลาเรียบร้อย');
                $('#leaveTable').DataTable().ajax.reload();
            } else {
                showError(result.error || 'เกิดข้อผิดพลาด');
            }
        } catch (error) {
            showError('เกิดข้อผิดพลาดในการยกเลิก');
        }
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
