-- =====================================================
-- Database Schema: Backoffice กลุ่มบริษัทยะลานำรุ่ง
-- Version: 1.0.0
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS backoffice_numrung 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE backoffice_numrung;

-- Drop tables if exist (in reverse order of dependencies)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS system_logs;
DROP TABLE IF EXISTS payroll_uploads;
DROP TABLE IF EXISTS day_swaps;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS leave_requests;
DROP TABLE IF EXISTS leave_types;
DROP TABLE IF EXISTS trainings;
DROP TABLE IF EXISTS employee_transfers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS positions;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS branches;
DROP TABLE IF EXISTS companies;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Table: companies (บริษัท)
-- =====================================================
CREATE TABLE IF NOT EXISTS companies (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    company_code VARCHAR(20) NOT NULL UNIQUE,
    company_name VARCHAR(255) NOT NULL,
    tax_id VARCHAR(20),
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(100),
    logo VARCHAR(255),
    status TINYINT DEFAULT 1 COMMENT '1=active, 0=inactive',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Table: branches (สาขา)
-- =====================================================
CREATE TABLE IF NOT EXISTS branches (
    branch_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    branch_code VARCHAR(20) NOT NULL,
    branch_name VARCHAR(255) NOT NULL,
    address TEXT,
    phone VARCHAR(50),
    status TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE RESTRICT,
    UNIQUE KEY unique_branch_code (company_id, branch_code)
) ENGINE=InnoDB;

-- =====================================================
-- Table: departments (แผนก)
-- =====================================================
CREATE TABLE IF NOT EXISTS departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    branch_id INT NOT NULL,
    department_name VARCHAR(255) NOT NULL,
    status TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- Table: positions (ตำแหน่ง)
-- =====================================================
CREATE TABLE IF NOT EXISTS positions (
    position_id INT AUTO_INCREMENT PRIMARY KEY,
    position_name VARCHAR(255) NOT NULL,
    base_salary DECIMAL(12,2) DEFAULT 0,
    status TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Table: employees (พนักงาน)
-- =====================================================
CREATE TABLE IF NOT EXISTS employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(20) NOT NULL UNIQUE,
    citizen_id VARCHAR(13) UNIQUE,
    prefix VARCHAR(20),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    birth_date DATE,
    gender ENUM('male', 'female') DEFAULT 'male',
    blood_type VARCHAR(5),
    religion VARCHAR(50),
    marital_status VARCHAR(50),
    education_level VARCHAR(100),
    phone VARCHAR(50),
    email VARCHAR(100),
    address TEXT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_relation VARCHAR(50),
    emergency_contact_phone VARCHAR(50),
    start_date DATE,
    company_id INT NOT NULL,
    branch_id INT NOT NULL,
    department_id INT,
    position_id INT,
    supervisor_id INT,
    employee_type ENUM('monthly', 'daily') DEFAULT 'monthly' COMMENT 'รายเดือน/รายวัน',
    salary DECIMAL(12,2) DEFAULT 0,
    photo VARCHAR(255),
    status ENUM('active', 'resigned', 'suspended') DEFAULT 'active',
    resigned_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE RESTRICT,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE RESTRICT,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES positions(position_id) ON DELETE SET NULL,
    FOREIGN KEY (supervisor_id) REFERENCES employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Table: users (ผู้ใช้ระบบ) - ต้องสร้างก่อน employee_transfers
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'hr', 'supervisor', 'employee') DEFAULT 'employee',
    employee_id INT,
    company_id INT,
    branch_id INT,
    status TINYINT DEFAULT 1,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE SET NULL,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Table: employee_transfers (ประวัติการย้ายหน่วยงาน)
-- =====================================================
CREATE TABLE IF NOT EXISTS employee_transfers (
    transfer_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    from_company_id INT,
    from_branch_id INT,
    from_department_id INT,
    from_position_id INT,
    to_company_id INT,
    to_branch_id INT,
    to_department_id INT,
    to_position_id INT,
    transfer_date DATE NOT NULL,
    reason TEXT,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (from_company_id) REFERENCES companies(company_id) ON DELETE SET NULL,
    FOREIGN KEY (to_company_id) REFERENCES companies(company_id) ON DELETE SET NULL,
    FOREIGN KEY (from_branch_id) REFERENCES branches(branch_id) ON DELETE SET NULL,
    FOREIGN KEY (to_branch_id) REFERENCES branches(branch_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Table: trainings (ประวัติการฝึกอบรม)
-- =====================================================
CREATE TABLE IF NOT EXISTS trainings (
    training_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    training_date DATE NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    institution VARCHAR(255),
    hours INT,
    certificate_file VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table: leave_types (ประเภทการลา)
-- =====================================================
CREATE TABLE IF NOT EXISTS leave_types (
    leave_type_id INT AUTO_INCREMENT PRIMARY KEY,
    leave_name VARCHAR(100) NOT NULL,
    max_days_per_year INT DEFAULT 0,
    description TEXT,
    status TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Table: leave_requests (ใบลา)
-- =====================================================
CREATE TABLE IF NOT EXISTS leave_requests (
    leave_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days DECIMAL(4,1) NOT NULL,
    reason TEXT,
    attachment VARCHAR(255),
    
    -- Supervisor approval (ขั้นที่ 1)
    supervisor_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    supervisor_approved_by INT,
    supervisor_approved_at DATETIME,
    supervisor_comment TEXT,
    
    -- HR approval (ขั้นที่ 2)
    hr_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    hr_approved_by INT,
    hr_approved_at DATETIME,
    hr_comment TEXT,
    
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(leave_type_id) ON DELETE RESTRICT,
    FOREIGN KEY (supervisor_approved_by) REFERENCES employees(employee_id) ON DELETE SET NULL,
    FOREIGN KEY (hr_approved_by) REFERENCES employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Table: attendance (ข้อมูลเข้า-ออกงาน)
-- =====================================================
CREATE TABLE IF NOT EXISTS attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    work_date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    work_hours DECIMAL(4,2),
    status ENUM('present', 'absent', 'late', 'leave', 'holiday') DEFAULT 'present',
    note TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (employee_id, work_date)
) ENGINE=InnoDB;

-- =====================================================
-- Table: day_swaps (การสลับวันหยุด)
-- =====================================================
CREATE TABLE IF NOT EXISTS day_swaps (
    swap_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    original_day_off DATE NOT NULL COMMENT 'วันหยุดเดิม',
    new_day_off DATE NOT NULL COMMENT 'วันหยุดใหม่',
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    approved_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Table: payroll_uploads (อัพโหลดไฟล์สแกนนิ้ว)
-- =====================================================
CREATE TABLE IF NOT EXISTS payroll_uploads (
    upload_id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    total_records INT DEFAULT 0,
    status ENUM('pending', 'processed', 'error') DEFAULT 'pending',
    uploaded_by INT,
    processed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Table: system_logs (บันทึกการใช้งานระบบ)
-- =====================================================
CREATE TABLE IF NOT EXISTS system_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_data JSON,
    new_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Indexes for better performance
-- =====================================================
CREATE INDEX idx_employees_company ON employees(company_id);
CREATE INDEX idx_employees_branch ON employees(branch_id);
CREATE INDEX idx_employees_status ON employees(status);
CREATE INDEX idx_leave_requests_employee ON leave_requests(employee_id);
CREATE INDEX idx_leave_requests_status ON leave_requests(status);
CREATE INDEX idx_attendance_employee_date ON attendance(employee_id, work_date);
CREATE INDEX idx_transfers_employee ON employee_transfers(employee_id);
