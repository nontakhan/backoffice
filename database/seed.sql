-- =====================================================
-- Seed Data: Backoffice กลุ่มบริษัทยะลานำรุ่ง
-- =====================================================

USE backoffice_numrung;

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- =====================================================
-- Companies (5 บริษัท)
-- =====================================================
INSERT INTO companies (company_code, company_name, tax_id, address, phone) VALUES
('YNR', 'บริษัท ยะลานำรุ่ง จำกัด', '0955538000001', 'ยะลา', '073-123456'),
('NRK', 'บริษัท นำรุ่งเคหะภัณฑ์ จำกัด', '0955538000002', 'ยะลา', '073-123457'),
('NRP', 'บริษัท นำรุ่งพูล จำกัด', '0955538000003', 'ยะลา', '073-123458'),
('NRC', 'บริษัท นำรุ่งคอนกรีต จำกัด', '0955538000004', 'ยะลา', '073-123459'),
('NRB', 'บริษัท นำรุ่งธุรกิจ จำกัด', '0955538000005', 'ยะลา', '073-123460');

-- =====================================================
-- Branches (สาขา)
-- =====================================================
-- บริษัท ยะลานำรุ่ง จำกัด
INSERT INTO branches (company_id, branch_code, branch_name, address) VALUES
(1, 'YNR-HQ', 'สนง.ใหญ่', 'ยะลา'),
(1, 'YNR-NTL', 'หน้าตลาด', 'ยะลา'),
(1, 'YNR-TLK', 'ตลาดเก่า', 'ยะลา'),
(1, 'YNR-TSP', 'ท่าสาป', 'ยะลา');

-- บริษัท นำรุ่งเคหะภัณฑ์ จำกัด
INSERT INTO branches (company_id, branch_code, branch_name, address) VALUES
(2, 'NRK-HP', 'หน้าโรงพยาบาล', 'ยะลา'),
(2, 'NRK-BT', 'เบตง', 'เบตง');

-- บริษัท นำรุ่งพูล จำกัด
INSERT INTO branches (company_id, branch_code, branch_name, address) VALUES
(3, 'NRP-HQ', 'สนง.ใหญ่', 'ยะลา');

-- บริษัท นำรุ่งคอนกรีต จำกัด
INSERT INTO branches (company_id, branch_code, branch_name, address) VALUES
(4, 'NRC-HQ', 'สนง.ใหญ่', 'ยะลา'),
(4, 'NRC-PC', 'พีคลาส', 'ยะลา');

-- บริษัท นำรุ่งธุรกิจ จำกัด
INSERT INTO branches (company_id, branch_code, branch_name, address) VALUES
(5, 'NRB-HQ', 'สนง.ใหญ่', 'ยะลา');

-- =====================================================
-- Departments (แผนก) - ตัวอย่าง
-- =====================================================
INSERT INTO departments (branch_id, department_name) VALUES
(1, 'บริหาร'),
(1, 'บัญชี'),
(1, 'ขาย'),
(1, 'คลังสินค้า'),
(1, 'ขนส่ง');

-- =====================================================
-- Positions (ตำแหน่ง) - ตัวอย่าง
-- =====================================================
INSERT INTO positions (position_name, base_salary) VALUES
('กรรมการผู้จัดการ', 100000),
('ผู้จัดการ', 50000),
('หัวหน้าแผนก', 25000),
('พนักงานบัญชี', 18000),
('พนักงานขาย', 15000),
('พนักงานคลังสินค้า', 12000),
('พนักงานขนส่ง', 12000),
('พนักงานทั่วไป', 10000);

-- =====================================================
-- Leave Types (ประเภทการลา)
-- =====================================================
INSERT INTO leave_types (leave_name, max_days_per_year, description) VALUES
('ลาป่วย', 30, 'ลาป่วยตามกฎหมายแรงงาน'),
('ลากิจ', 6, 'ลากิจส่วนตัว'),
('ลาพักร้อน', 6, 'ลาพักร้อนประจำปี'),
('ลาคลอด', 98, 'ลาคลอดบุตร'),
('ลาบวช', 15, 'ลาอุปสมบท'),
('ลาเพื่อรับราชการทหาร', 60, 'ลาเพื่อรับราชการทหาร'),
('ลาไม่รับค่าจ้าง', 0, 'ลาโดยไม่รับค่าจ้าง');

-- =====================================================
-- Sample Employee
-- =====================================================
INSERT INTO employees (
    employee_code, citizen_id, prefix, first_name, last_name,
    birth_date, gender, blood_type, religion, marital_status, education_level,
    phone, email, address,
    emergency_contact_name, emergency_contact_relation, emergency_contact_phone,
    start_date, company_id, branch_id, department_id, position_id,
    employee_type, salary, status
) VALUES (
    'EMP001', '1959900024157', 'นางสาว', 'ใจดี', 'นามสกุล',
    '2000-04-04', 'female', 'A', 'พุทธ', 'โสด', 'ปริญญาตรี',
    '089-1234567', 'jaidi@example.com', '129/1 หมู่ - ต.ท่าสาป อ.เมือง จ.ยะลา 95000',
    'นาย', 'พ่อ', '089-9999999',
    '2565-01-01', 1, 4, 1, 3,
    'monthly', 25000, 'active'
);

-- =====================================================
-- Admin User
-- =====================================================
INSERT INTO users (username, password, role, employee_id, company_id, branch_id, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, NULL, 1);
-- Password: password

-- =====================================================
-- Sample HR User
-- =====================================================
INSERT INTO users (username, password, role, employee_id, company_id, branch_id, status) VALUES
('hr_ynr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hr', 1, 1, 1, 1);
-- Password: password
