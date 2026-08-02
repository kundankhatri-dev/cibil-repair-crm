<?php
// api/test.php
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Admin API is working!',
    'timestamp' => date('Y-m-d H:i:s')
]);
exit;
?>