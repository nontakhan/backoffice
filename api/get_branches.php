<?php
/**
 * Get Branches API
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$companyId = (int)($_GET['company_id'] ?? 0);

if (!$companyId) {
    jsonResponse([]);
}

// Check if company is accessible
$accessibleCompanyIds = getAccessibleCompanyIds();
if (!in_array($companyId, $accessibleCompanyIds)) {
    jsonResponse([]);
}

$pdo = getDBConnection();

$stmt = $pdo->prepare("
    SELECT branch_id, branch_name 
    FROM branches 
    WHERE company_id = ? AND status = 1
    ORDER BY branch_name
");
$stmt->execute([$companyId]);
$branches = $stmt->fetchAll();

jsonResponse($branches);
