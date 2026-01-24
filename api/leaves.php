<?php
/**
 * Leave Requests API
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['draw'])) {
            handleDataTables($pdo);
        } elseif (isset($_GET['id'])) {
            getLeaveRequest($pdo, (int)$_GET['id']);
        } else {
            getLeaveRequests($pdo);
        }
        break;
        
    case 'POST':
        createLeaveRequest($pdo);
        break;
        
    case 'PUT':
        updateLeaveRequest($pdo);
        break;
        
    case 'DELETE':
        cancelLeaveRequest($pdo);
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

/**
 * Handle DataTables
 */
function handleDataTables($pdo) {
    $draw = (int)$_GET['draw'];
    $start = (int)$_GET['start'];
    $length = (int)$_GET['length'];
    $search = $_GET['search']['value'] ?? '';
    $mode = $_GET['mode'] ?? 'list';
    $approvalMode = $_GET['approval_mode'] ?? 'supervisor';
    
    $baseQuery = "
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.employee_id
        JOIN leave_types lt ON lr.leave_type_id = lt.leave_type_id
        LEFT JOIN branches b ON e.branch_id = b.branch_id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Mode-specific filters
    if ($mode === 'pending') {
        if ($approvalMode === 'supervisor') {
            $baseQuery .= " AND lr.supervisor_status = 'pending'";
            
            // Supervisor can only see their branch
            if (!isAdmin() && !isHR()) {
                $baseQuery .= " AND e.branch_id = ?";
                $params[] = $_SESSION['branch_id'];
            }
        } else {
            // HR mode - show approved by supervisor, pending HR
            $baseQuery .= " AND lr.supervisor_status = 'approved' AND lr.hr_status = 'pending'";
            
            if (!isAdmin()) {
                $baseQuery .= " AND e.company_id = ?";
                $params[] = $_SESSION['company_id'];
            }
        }
    } else {
        // List mode - show based on role
        if (!isAdmin()) {
            if (isHR()) {
                $baseQuery .= " AND e.company_id = ?";
                $params[] = $_SESSION['company_id'];
            } elseif (isSupervisor()) {
                $baseQuery .= " AND e.branch_id = ?";
                $params[] = $_SESSION['branch_id'];
            } else {
                // Regular employee - only own leaves
                $baseQuery .= " AND lr.employee_id = ?";
                $params[] = $_SESSION['employee_id'];
            }
        }
    }
    
    // Search
    if (!empty($search)) {
        $baseQuery .= " AND (
            e.first_name LIKE ? OR
            e.last_name LIKE ? OR
            lt.leave_name LIKE ?
        )";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Count filtered
    $stmt = $pdo->prepare("SELECT COUNT(*) " . $baseQuery);
    $stmt->execute($params);
    $totalFiltered = $stmt->fetchColumn();
    
    // Get data
    $stmt = $pdo->prepare("
        SELECT lr.*, lt.leave_name, e.prefix, e.first_name, e.last_name,
               b.branch_name,
               CASE WHEN lr.employee_id = ? AND lr.status = 'pending' THEN 1 ELSE 0 END as can_cancel
        $baseQuery
        ORDER BY lr.created_at DESC
        LIMIT $length OFFSET $start
    ");
    
    array_unshift($params, $_SESSION['employee_id']);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    jsonResponse([
        'draw' => $draw,
        'recordsTotal' => $totalFiltered,
        'recordsFiltered' => $totalFiltered,
        'data' => $data
    ]);
}

/**
 * Get single leave request
 */
function getLeaveRequest($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT lr.*, lt.leave_name, e.prefix, e.first_name, e.last_name,
               c.company_name, b.branch_name
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.employee_id
        JOIN leave_types lt ON lr.leave_type_id = lt.leave_type_id
        LEFT JOIN companies c ON e.company_id = c.company_id
        LEFT JOIN branches b ON e.branch_id = b.branch_id
        WHERE lr.leave_id = ?
    ");
    $stmt->execute([$id]);
    $leave = $stmt->fetch();
    
    if (!$leave) {
        jsonResponse(['error' => 'Not found'], 404);
    }
    
    jsonResponse($leave);
}

/**
 * Get all leave requests
 */
function getLeaveRequests($pdo) {
    $baseQuery = "
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.employee_id
        JOIN leave_types lt ON lr.leave_type_id = lt.leave_type_id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Apply role-based filter
    if (!isAdmin()) {
        if (isHR()) {
            $baseQuery .= " AND e.company_id = ?";
            $params[] = $_SESSION['company_id'];
        } else {
            $baseQuery .= " AND lr.employee_id = ?";
            $params[] = $_SESSION['employee_id'];
        }
    }
    
    $stmt = $pdo->prepare("
        SELECT lr.*, lt.leave_name, e.prefix, e.first_name, e.last_name
        $baseQuery
        ORDER BY lr.created_at DESC
        LIMIT 100
    ");
    $stmt->execute($params);
    
    jsonResponse($stmt->fetchAll());
}

/**
 * Create leave request
 */
function createLeaveRequest($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate
    $required = ['leave_type_id', 'start_date', 'end_date', 'reason'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonResponse(['error' => "Missing required field: $field"], 400);
        }
    }
    
    $employeeId = $_SESSION['employee_id'];
    if (!$employeeId) {
        jsonResponse(['error' => 'No employee linked to this user'], 400);
    }
    
    // Calculate total days
    $startDate = new DateTime($data['start_date']);
    $endDate = new DateTime($data['end_date']);
    $totalDays = $endDate->diff($startDate)->days + 1;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO leave_requests (
                employee_id, leave_type_id, start_date, end_date,
                total_days, reason, status
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $employeeId,
            $data['leave_type_id'],
            $data['start_date'],
            $data['end_date'],
            $totalDays,
            $data['reason']
        ]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Leave request submitted successfully',
            'leave_id' => $pdo->lastInsertId()
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Failed to create leave request: ' . $e->getMessage()], 500);
    }
}

/**
 * Update leave request (approval)
 */
function updateLeaveRequest($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['leave_id']) || empty($data['action'])) {
        jsonResponse(['error' => 'Missing required fields'], 400);
    }
    
    $leaveId = (int)$data['leave_id'];
    $action = $data['action']; // 'approve' or 'reject'
    $approvalMode = $data['approval_mode'] ?? 'supervisor';
    $comment = $data['comment'] ?? '';
    
    $status = $action === 'approve' ? 'approved' : 'rejected';
    $approverId = $_SESSION['employee_id'];
    
    try {
        if ($approvalMode === 'supervisor') {
            $stmt = $pdo->prepare("
                UPDATE leave_requests SET
                    supervisor_status = ?,
                    supervisor_approved_by = ?,
                    supervisor_approved_at = NOW(),
                    supervisor_comment = ?,
                    status = CASE 
                        WHEN ? = 'rejected' THEN 'rejected'
                        ELSE status
                    END,
                    updated_at = NOW()
                WHERE leave_id = ?
            ");
            $stmt->execute([$status, $approverId, $comment, $status, $leaveId]);
        } else {
            // HR approval
            $finalStatus = $status;
            
            $stmt = $pdo->prepare("
                UPDATE leave_requests SET
                    hr_status = ?,
                    hr_approved_by = ?,
                    hr_approved_at = NOW(),
                    hr_comment = ?,
                    status = ?,
                    updated_at = NOW()
                WHERE leave_id = ?
            ");
            $stmt->execute([$status, $approverId, $comment, $finalStatus, $leaveId]);
        }
        
        $actionText = $action === 'approve' ? 'อนุมัติ' : 'ไม่อนุมัติ';
        jsonResponse([
            'success' => true,
            'message' => $actionText . 'เรียบร้อยแล้ว'
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Failed to update: ' . $e->getMessage()], 500);
    }
}

/**
 * Cancel leave request
 */
function cancelLeaveRequest($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['leave_id'])) {
        jsonResponse(['error' => 'Missing leave_id'], 400);
    }
    
    $leaveId = (int)$data['leave_id'];
    
    // Check if user owns this leave request
    $stmt = $pdo->prepare("SELECT employee_id, status FROM leave_requests WHERE leave_id = ?");
    $stmt->execute([$leaveId]);
    $leave = $stmt->fetch();
    
    if (!$leave) {
        jsonResponse(['error' => 'Not found'], 404);
    }
    
    if ($leave['employee_id'] != $_SESSION['employee_id'] && !isAdmin()) {
        jsonResponse(['error' => 'Access denied'], 403);
    }
    
    if ($leave['status'] !== 'pending') {
        jsonResponse(['error' => 'Cannot cancel - leave is already processed'], 400);
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE leave_requests SET status = 'cancelled', updated_at = NOW()
            WHERE leave_id = ?
        ");
        $stmt->execute([$leaveId]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Leave request cancelled'
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Failed to cancel: ' . $e->getMessage()], 500);
    }
}
