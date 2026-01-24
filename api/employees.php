<?php
/**
 * Employees API
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Check login
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Handle DataTables server-side processing
if ($method === 'GET' && isset($_GET['draw'])) {
    handleDataTables($pdo);
    exit;
}

// Handle CRUD operations
switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getEmployee($pdo, (int)$_GET['id']);
        } else {
            getEmployees($pdo);
        }
        break;
        
    case 'POST':
        createEmployee($pdo);
        break;
        
    case 'PUT':
        updateEmployee($pdo);
        break;
        
    case 'DELETE':
        deleteEmployee($pdo);
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

/**
 * Handle DataTables server-side processing
 */
function handleDataTables($pdo) {
    $draw = (int)$_GET['draw'];
    $start = (int)$_GET['start'];
    $length = (int)$_GET['length'];
    $search = $_GET['search']['value'] ?? '';
    
    // Filters
    $companyId = $_GET['company_id'] ?? '';
    $branchId = $_GET['branch_id'] ?? '';
    $status = $_GET['status'] ?? '';
    
    // Build base query
    $baseQuery = "
        FROM employees e
        LEFT JOIN companies c ON e.company_id = c.company_id
        LEFT JOIN branches b ON e.branch_id = b.branch_id
        LEFT JOIN positions p ON e.position_id = p.position_id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Access control
    $accessibleCompanyIds = getAccessibleCompanyIds();
    if (empty($accessibleCompanyIds)) {
        // Return empty if no accessible companies
        jsonResponse([
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => []
        ]);
        return;
    }
    $placeholders = implode(',', array_fill(0, count($accessibleCompanyIds), '?'));
    $baseQuery .= " AND e.company_id IN ($placeholders)";
    $params = array_merge($params, $accessibleCompanyIds);
    
    // Apply filters
    if (!empty($companyId)) {
        $baseQuery .= " AND e.company_id = ?";
        $params[] = $companyId;
    }
    
    if (!empty($branchId)) {
        $baseQuery .= " AND e.branch_id = ?";
        $params[] = $branchId;
    }
    
    if (!empty($status)) {
        $baseQuery .= " AND e.status = ?";
        $params[] = $status;
    }
    
    // Search
    if (!empty($search)) {
        $baseQuery .= " AND (
            e.employee_code LIKE ? OR
            e.first_name LIKE ? OR
            e.last_name LIKE ? OR
            e.nickname LIKE ? OR
            e.citizen_id LIKE ? OR
            CONCAT(e.first_name, ' ', e.last_name) LIKE ?
        )";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Get total records
    $stmt = $pdo->prepare("SELECT COUNT(*) " . $baseQuery);
    $stmt->execute($params);
    $totalFiltered = $stmt->fetchColumn();
    
    // Get total records without filter
    $accessibleCompanyIds = getAccessibleCompanyIds();
    $placeholders = implode(',', array_fill(0, count($accessibleCompanyIds), '?'));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE company_id IN ($placeholders)");
    $stmt->execute($accessibleCompanyIds);
    $totalRecords = $stmt->fetchColumn();
    
    // Order
    $orderColumn = (int)($_GET['order'][0]['column'] ?? 0);
    $orderDir = strtoupper($_GET['order'][0]['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
    
    $columns = ['e.employee_code', 'e.first_name', 'e.nickname', 'p.position_name', 'c.company_name', 'b.branch_name', 'e.employee_type'];
    $orderBy = $columns[$orderColumn] ?? 'e.employee_code';
    
    // Get data
    $stmt = $pdo->prepare("
        SELECT e.employee_id, e.employee_code, e.prefix, e.first_name, e.last_name, e.nickname,
               e.citizen_id, e.phone, e.employee_type, e.status, e.photo,
               c.company_name, b.branch_name, p.position_name
        $baseQuery
        ORDER BY $orderBy $orderDir
        LIMIT $length OFFSET $start
    ");
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    jsonResponse([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalFiltered,
        'data' => $data
    ]);
}

/**
 * Get single employee
 */
function getEmployee($pdo, $id) {
    if (!canAccessEmployee($id)) {
        jsonResponse(['error' => 'Access denied'], 403);
    }
    
    $stmt = $pdo->prepare("
        SELECT e.*, 
               c.company_name, b.branch_name, d.department_name, p.position_name
        FROM employees e
        LEFT JOIN companies c ON e.company_id = c.company_id
        LEFT JOIN branches b ON e.branch_id = b.branch_id
        LEFT JOIN departments d ON e.department_id = d.department_id
        LEFT JOIN positions p ON e.position_id = p.position_id
        WHERE e.employee_id = ?
    ");
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        jsonResponse(['error' => 'Employee not found'], 404);
    }
    
    jsonResponse($employee);
}

/**
 * Get all employees
 */
function getEmployees($pdo) {
    $accessibleCompanyIds = getAccessibleCompanyIds();
    $placeholders = implode(',', array_fill(0, count($accessibleCompanyIds), '?'));
    
    $stmt = $pdo->prepare("
        SELECT e.employee_id, e.employee_code, e.prefix, e.first_name, e.last_name,
               c.company_name, b.branch_name, p.position_name
        FROM employees e
        LEFT JOIN companies c ON e.company_id = c.company_id
        LEFT JOIN branches b ON e.branch_id = b.branch_id
        LEFT JOIN positions p ON e.position_id = p.position_id
        WHERE e.company_id IN ($placeholders) AND e.status = 'active'
        ORDER BY e.first_name, e.last_name
    ");
    $stmt->execute($accessibleCompanyIds);
    $employees = $stmt->fetchAll();
    
    jsonResponse($employees);
}

/**
 * Create employee
 */
function createEmployee($pdo) {
    if (!isHR()) {
        jsonResponse(['error' => 'Access denied'], 403);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['first_name', 'last_name', 'company_id', 'branch_id'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonResponse(['error' => "Missing required field: $field"], 400);
        }
    }
    
    // Check if company is accessible
    $accessibleCompanyIds = getAccessibleCompanyIds();
    if (!in_array($data['company_id'], $accessibleCompanyIds)) {
        jsonResponse(['error' => 'Access denied to this company'], 403);
    }
    
    // Generate employee code
    $stmt = $pdo->query("SELECT MAX(employee_id) FROM employees");
    $maxId = $stmt->fetchColumn() ?? 0;
    $employeeCode = 'EMP' . str_pad($maxId + 1, 4, '0', STR_PAD_LEFT);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO employees (
                employee_code, citizen_id, prefix, first_name, last_name, nickname,
                birth_date, gender, blood_type, religion, marital_status, education_level,
                phone, email, address,
                emergency_contact_name, emergency_contact_relation, emergency_contact_phone,
                start_date, company_id, branch_id, department_id, position_id,
                supervisor_id, employee_type, salary, status
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, 'active'
            )
        ");
        
        $stmt->execute([
            $employeeCode,
            $data['citizen_id'] ?? null,
            $data['prefix'] ?? '',
            $data['first_name'],
            $data['last_name'],
            $data['nickname'] ?? null,
            $data['birth_date'] ?? null,
            $data['gender'] ?? 'male',
            $data['blood_type'] ?? null,
            $data['religion'] ?? null,
            $data['marital_status'] ?? null,
            $data['education_level'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $data['emergency_contact_name'] ?? null,
            $data['emergency_contact_relation'] ?? null,
            $data['emergency_contact_phone'] ?? null,
            $data['start_date'] ?? date('Y-m-d'),
            $data['company_id'],
            $data['branch_id'],
            $data['department_id'] ?? null,
            $data['position_id'] ?? null,
            $data['supervisor_id'] ?? null,
            $data['employee_type'] ?? 'monthly',
            $data['salary'] ?? 0
        ]);
        
        $employeeId = $pdo->lastInsertId();
        
        jsonResponse([
            'success' => true,
            'message' => 'Employee created successfully',
            'employee_id' => $employeeId,
            'employee_code' => $employeeCode
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Failed to create employee: ' . $e->getMessage()], 500);
    }
}

/**
 * Update employee
 */
function updateEmployee($pdo) {
    if (!isHR()) {
        jsonResponse(['error' => 'Access denied'], 403);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['employee_id'])) {
        jsonResponse(['error' => 'Employee ID is required'], 400);
    }
    
    $employeeId = (int)$data['employee_id'];
    
    if (!canAccessEmployee($employeeId)) {
        jsonResponse(['error' => 'Access denied'], 403);
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE employees SET
                citizen_id = ?, prefix = ?, first_name = ?, last_name = ?, nickname = ?,
                birth_date = ?, gender = ?, blood_type = ?, religion = ?,
                marital_status = ?, education_level = ?,
                phone = ?, email = ?, address = ?,
                emergency_contact_name = ?, emergency_contact_relation = ?, emergency_contact_phone = ?,
                start_date = ?, company_id = ?, branch_id = ?,
                department_id = ?, position_id = ?, supervisor_id = ?,
                employee_type = ?, salary = ?, status = ?,
                updated_at = NOW()
            WHERE employee_id = ?
        ");
        
        $stmt->execute([
            $data['citizen_id'] ?? null,
            $data['prefix'] ?? '',
            $data['first_name'],
            $data['last_name'],
            $data['nickname'] ?? null,
            $data['birth_date'] ?? null,
            $data['gender'] ?? 'male',
            $data['blood_type'] ?? null,
            $data['religion'] ?? null,
            $data['marital_status'] ?? null,
            $data['education_level'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $data['emergency_contact_name'] ?? null,
            $data['emergency_contact_relation'] ?? null,
            $data['emergency_contact_phone'] ?? null,
            $data['start_date'] ?? null,
            $data['company_id'],
            $data['branch_id'],
            $data['department_id'] ?? null,
            $data['position_id'] ?? null,
            $data['supervisor_id'] ?? null,
            $data['employee_type'] ?? 'monthly',
            $data['salary'] ?? 0,
            $data['status'] ?? 'active',
            $employeeId
        ]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Employee updated successfully'
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Failed to update employee: ' . $e->getMessage()], 500);
    }
}

/**
 * Delete employee (soft delete - change status to resigned)
 */
function deleteEmployee($pdo) {
    if (!isAdmin()) {
        jsonResponse(['error' => 'Access denied'], 403);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['employee_id'])) {
        jsonResponse(['error' => 'Employee ID is required'], 400);
    }
    
    $employeeId = (int)$data['employee_id'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE employees SET 
                status = 'resigned', 
                resigned_date = CURDATE(),
                updated_at = NOW()
            WHERE employee_id = ?
        ");
        $stmt->execute([$employeeId]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Employee deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Failed to delete employee: ' . $e->getMessage()], 500);
    }
}
