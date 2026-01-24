/**
 * Main JavaScript
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

// SweetAlert2 Custom Theme
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    background: '#22222e',
    color: '#f8fafc',
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
});

// SweetAlert2 Default Config
const SwalCustom = Swal.mixin({
    background: '#22222e',
    color: '#f8fafc',
    confirmButtonColor: '#d946ef',
    cancelButtonColor: '#64748b',
    customClass: {
        popup: 'swal-custom-popup'
    }
});

/**
 * Toggle Sidebar
 */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');

    sidebar.classList.toggle('open');
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('collapsed');
}

/**
 * Show Loading
 */
function showLoading(message = 'กำลังโหลด...') {
    SwalCustom.fire({
        title: message,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Hide Loading
 */
function hideLoading() {
    Swal.close();
}

/**
 * Show Success Message
 */
function showSuccess(message, callback = null) {
    Toast.fire({
        icon: 'success',
        title: message
    }).then(() => {
        if (callback) callback();
    });
}

/**
 * Show Error Message
 */
function showError(message) {
    Toast.fire({
        icon: 'error',
        title: message
    });
}

/**
 * Show Warning Message
 */
function showWarning(message) {
    Toast.fire({
        icon: 'warning',
        title: message
    });
}

/**
 * Show Info Message
 */
function showInfo(message) {
    Toast.fire({
        icon: 'info',
        title: message
    });
}

/**
 * Confirm Dialog
 */
function confirmDialog(title, text, callback) {
    SwalCustom.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ใช่, ดำเนินการ',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed && callback) {
            callback();
        }
    });
}

/**
 * Confirm Delete Dialog
 */
function confirmDelete(callback) {
    SwalCustom.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบรายการนี้หรือไม่? การดำเนินการนี้ไม่สามารถย้อนกลับได้',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ใช่, ลบเลย',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#ef4444',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed && callback) {
            callback();
        }
    });
}

/**
 * Format Number with Commas
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * Format Currency
 */
function formatCurrency(num) {
    return new Intl.NumberFormat('th-TH', {
        style: 'currency',
        currency: 'THB'
    }).format(num);
}

/**
 * Format Date to Thai
 */
function formatThaiDate(dateStr) {
    if (!dateStr) return '-';

    const months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
        'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    const date = new Date(dateStr);
    const d = date.getDate();
    const m = date.getMonth() + 1;
    const y = date.getFullYear() + 543;

    return `${d} ${months[m]} ${y}`;
}

/**
 * AJAX Request Helper
 */
async function apiRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    const config = { ...defaultOptions, ...options };

    if (config.body && typeof config.body === 'object') {
        config.body = JSON.stringify(config.body);
    }

    try {
        const response = await fetch(url, config);
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'เกิดข้อผิดพลาด');
        }

        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

/**
 * GET Request
 */
async function apiGet(url) {
    return apiRequest(url);
}

/**
 * POST Request
 */
async function apiPost(url, data) {
    return apiRequest(url, {
        method: 'POST',
        body: data
    });
}

/**
 * PUT Request
 */
async function apiPut(url, data) {
    return apiRequest(url, {
        method: 'PUT',
        body: data
    });
}

/**
 * DELETE Request
 */
async function apiDelete(url) {
    return apiRequest(url, {
        method: 'DELETE'
    });
}

/**
 * Initialize DataTables with Thai language
 */
function initDataTable(selector, options = {}) {
    const defaultOptions = {
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
            },
            loadingRecords: 'กำลังโหลด...',
            processing: 'กำลังประมวลผล...'
        },
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'ทั้งหมด']],
        responsive: true,
        order: []
    };

    return $(selector).DataTable({ ...defaultOptions, ...options });
}

/**
 * Form Validation
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        field.classList.remove('is-invalid');

        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        }
    });

    if (!isValid) {
        showError('กรุณากรอกข้อมูลให้ครบถ้วน');
    }

    return isValid;
}

/**
 * Preview Image Upload
 */
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById(previewId).src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * Copy to Clipboard
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showSuccess('คัดลอกแล้ว');
    } catch (err) {
        showError('ไม่สามารถคัดลอกได้');
    }
}

/**
 * Debounce Function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Theme Toggle - Dark/Light Mode
 */
function initThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    const themeIconDark = document.getElementById('themeIconDark');
    const themeIconLight = document.getElementById('themeIconLight');

    // Get saved theme or default to dark
    const savedTheme = localStorage.getItem('theme') || 'dark';
    setTheme(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
            localStorage.setItem('theme', newTheme);
        });
    }
}

function setTheme(theme) {
    const themeIconDark = document.getElementById('themeIconDark');
    const themeIconLight = document.getElementById('themeIconLight');

    if (theme === 'light') {
        document.documentElement.setAttribute('data-theme', 'light');
        if (themeIconDark) themeIconDark.style.display = 'none';
        if (themeIconLight) themeIconLight.style.display = 'inline';
    } else {
        document.documentElement.removeAttribute('data-theme');
        if (themeIconDark) themeIconDark.style.display = 'inline';
        if (themeIconLight) themeIconLight.style.display = 'none';
    }
}

// Initialize theme before page renders (prevent flash)
(function () {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    if (savedTheme === 'light') {
        document.documentElement.setAttribute('data-theme', 'light');
    }
})();

/**
 * Initialize on Document Ready
 */
document.addEventListener('DOMContentLoaded', function () {
    // Initialize theme toggle
    initThemeToggle();

    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card, .stat-card, .company-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.05}s`;
        card.classList.add('fade-in');
    });

    // Mobile sidebar toggle
    const sidebarOverlay = document.createElement('div');
    sidebarOverlay.className = 'sidebar-overlay';
    sidebarOverlay.onclick = toggleSidebar;
    document.body.appendChild(sidebarOverlay);
});

// Add custom styles for SweetAlert
const style = document.createElement('style');
style.textContent = `
    .swal-custom-popup {
        border: 1px solid #3f3f50;
        border-radius: 16px;
    }
    
    .swal2-title {
        font-family: 'Sarabun', sans-serif;
    }
    
    .swal2-html-container {
        font-family: 'Sarabun', sans-serif;
    }
    
    .swal2-styled.swal2-confirm {
        font-family: 'Sarabun', sans-serif;
        font-weight: 500;
        border-radius: 10px;
        padding: 0.75rem 1.5rem;
    }
    
    .swal2-styled.swal2-cancel {
        font-family: 'Sarabun', sans-serif;
        font-weight: 500;
        border-radius: 10px;
        padding: 0.75rem 1.5rem;
    }
    
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.25s ease;
    }
    
    .sidebar.open ~ .sidebar-overlay {
        opacity: 1;
        visibility: visible;
    }
    
    @media (min-width: 993px) {
        .sidebar-overlay {
            display: none;
        }
    }
    
    .form-control.is-invalid {
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
    }
`;
document.head.appendChild(style);
