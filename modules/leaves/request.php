<?php
/**
 * Leave Request Form
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'ยื่นใบลา';
$pdo = getDBConnection();

// Get leave types
$stmt = $pdo->query("SELECT * FROM leave_types WHERE status = 1 ORDER BY leave_type_id");
$leaveTypes = $stmt->fetchAll();

// Get current user's leave balance (simplified)
$employeeId = $_SESSION['employee_id'];
$currentYear = date('Y');

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Content -->
<div class="page-content">
    <!-- Breadcrumb -->
    <nav class="mb-4">
        <a href="<?= BASE_URL ?>modules/leaves/" class="text-muted">
            <i class="fas fa-arrow-left"></i> กลับไปรายการลา
        </a>
    </nav>
    
    <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <!-- Leave Form -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar-plus"></i> ยื่นใบลา
                </h3>
            </div>
            <div class="card-body">
                <form id="leaveForm">
                    <div class="form-group">
                        <label class="form-label required">ประเภทการลา</label>
                        <select name="leave_type_id" class="form-control" required id="leaveType">
                            <option value="">-- เลือกประเภทการลา --</option>
                            <?php foreach ($leaveTypes as $type): ?>
                            <option value="<?= $type['leave_type_id'] ?>" data-max="<?= $type['max_days_per_year'] ?>">
                                <?= htmlspecialchars($type['leave_name']) ?> 
                                (สิทธิ์ <?= $type['max_days_per_year'] ?> วัน/ปี)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2">
                        <div class="form-group">
                            <label class="form-label required">วันที่เริ่มลา</label>
                            <input type="date" name="start_date" class="form-control" required id="startDate"
                                   min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">วันที่สิ้นสุด</label>
                            <input type="date" name="end_date" class="form-control" required id="endDate"
                                   min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">จำนวนวัน</label>
                        <input type="number" name="total_days" class="form-control" readonly id="totalDays"
                               style="background: var(--bg-darker);">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">เหตุผลการลา</label>
                        <textarea name="reason" class="form-control" rows="3" required
                                  placeholder="กรุณาระบุเหตุผลในการลา..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">เอกสารแนบ</label>
                        <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text">รองรับไฟล์ PDF, JPG, PNG ขนาดไม่เกิน 5MB</div>
                    </div>
                </form>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>modules/leaves/" class="btn btn-outline">
                        <i class="fas fa-times"></i> ยกเลิก
                    </a>
                    <button type="submit" form="leaveForm" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> ส่งใบลา
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Leave Balance -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-chart-pie"></i> สิทธิ์การลาคงเหลือ
                    </h4>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($leaveTypes as $type): ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted"><?= htmlspecialchars($type['leave_name']) ?></span>
                            <span class="badge badge-primary"><?= $type['max_days_per_year'] ?> วัน</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle alert-icon"></i>
                <div>
                    <strong>ขั้นตอนการอนุมัติ</strong>
                    <ol class="mt-2 mb-0 ps-3">
                        <li>ยื่นใบลา</li>
                        <li>หัวหน้าอนุมัติ</li>
                        <li>HR อนุมัติ</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate total days
function calculateDays() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        if (end >= start) {
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            document.getElementById('totalDays').value = diffDays;
        } else {
            document.getElementById('totalDays').value = '';
        }
    }
}

document.getElementById('startDate').addEventListener('change', function() {
    document.getElementById('endDate').min = this.value;
    calculateDays();
});

document.getElementById('endDate').addEventListener('change', calculateDays);

// Handle form submit
document.getElementById('leaveForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {};
    formData.forEach((value, key) => {
        if (key !== 'attachment') data[key] = value;
    });
    
    // Validate
    if (!data.leave_type_id || !data.start_date || !data.end_date || !data.reason) {
        showError('กรุณากรอกข้อมูลให้ครบถ้วน');
        return;
    }
    
    if (new Date(data.end_date) < new Date(data.start_date)) {
        showError('วันที่สิ้นสุดต้องไม่น้อยกว่าวันที่เริ่มลา');
        return;
    }
    
    showLoading('กำลังส่งใบลา...');
    
    try {
        const response = await fetch('<?= BASE_URL ?>api/leaves.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            SwalCustom.fire({
                icon: 'success',
                title: 'ส่งใบลาเรียบร้อย',
                text: 'รอการอนุมัติจากหัวหน้า',
                confirmButtonText: 'ตกลง'
            }).then(() => {
                window.location.href = '<?= BASE_URL ?>modules/leaves/';
            });
        } else {
            showError(result.error || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        showError('เกิดข้อผิดพลาดในการส่งใบลา');
        console.error(error);
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
