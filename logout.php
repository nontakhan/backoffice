<?php
/**
 * Logout
 * Backoffice กลุ่มบริษัทยะลานำรุ่ง
 */

require_once __DIR__ . '/includes/auth.php';

logout();
header('Location: index.php?msg=logged_out');
exit;
