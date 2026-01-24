<?php
/**
 * Authentication & Authorization
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Login user
 */
function login($username, $password) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT u.*, e.first_name, e.last_name, e.photo,
               c.company_name, b.branch_name
        FROM users u
        LEFT JOIN employees e ON u.employee_id = e.employee_id
        LEFT JOIN companies c ON u.company_id = c.company_id
        LEFT JOIN branches b ON u.branch_id = b.branch_id
        WHERE u.username = ? AND u.status = 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Update last login
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $updateStmt->execute([$user['user_id']]);
        
        // Set session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['employee_id'] = $user['employee_id'];
        $_SESSION['company_id'] = $user['company_id'];
        $_SESSION['branch_id'] = $user['branch_id'];
        $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['photo'] = $user['photo'];
        $_SESSION['company_name'] = $user['company_name'];
        $_SESSION['branch_name'] = $user['branch_name'];
        $_SESSION['login_time'] = time();
        
        return true;
    }
    
    return false;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user role
 */
function getRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Check if user has specific role
 */
function hasRole($roles) {
    if (!isLoggedIn()) return false;
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    return in_array($_SESSION['role'], $roles);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if user is HR
 */
function isHR() {
    return hasRole(['admin', 'hr']);
}

/**
 * Check if user is supervisor
 */
function isSupervisor() {
    return hasRole(['admin', 'hr', 'supervisor']);
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('index.php?msg=login_required');
    }
    
    // Check session timeout
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > SESSION_LIFETIME)) {
        logout();
        redirect('index.php?msg=session_expired');
    }
    
    // Refresh session time
    $_SESSION['login_time'] = time();
}

/**
 * Require specific role
 */
function requireRole($roles) {
    requireLogin();
    
    if (!hasRole($roles)) {
        redirect('dashboard.php?msg=access_denied');
    }
}

/**
 * Logout user
 */
function logout() {
    session_unset();
    session_destroy();
}

/**
 * Get accessible company IDs for current user
 */
function getAccessibleCompanyIds() {
    if (isAdmin()) {
        // Admin can access all companies
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT company_id FROM companies WHERE status = 1");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // HR and others can only access their own company
    return [$_SESSION['company_id']];
}

/**
 * Get accessible branch IDs for current user
 */
function getAccessibleBranchIds() {
    if (isAdmin()) {
        // Admin can access all branches
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT branch_id FROM branches WHERE status = 1");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    if (isHR()) {
        // HR can access all branches in their company
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT branch_id FROM branches WHERE company_id = ? AND status = 1");
        $stmt->execute([$_SESSION['company_id']]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Supervisor and employee can only access their own branch
    return [$_SESSION['branch_id']];
}

/**
 * Check if user can access specific employee
 */
function canAccessEmployee($employeeId) {
    if (isAdmin()) return true;
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT company_id, branch_id FROM employees WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();
    
    if (!$employee) return false;
    
    if (isHR() && $employee['company_id'] == $_SESSION['company_id']) {
        return true;
    }
    
    if (isSupervisor() && $employee['branch_id'] == $_SESSION['branch_id']) {
        return true;
    }
    
    // Employee can only access themselves
    return $_SESSION['employee_id'] == $employeeId;
}

/**
 * Get current user info
 */
function getCurrentUser() {
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'employee_id' => $_SESSION['employee_id'] ?? null,
        'company_id' => $_SESSION['company_id'] ?? null,
        'branch_id' => $_SESSION['branch_id'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'photo' => $_SESSION['photo'] ?? null,
        'company_name' => $_SESSION['company_name'] ?? null,
        'branch_name' => $_SESSION['branch_name'] ?? null,
    ];
}
?>
