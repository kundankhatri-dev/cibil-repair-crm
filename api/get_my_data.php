<?php
// api/get_my_data.php
require_once __DIR__ . '/init.php';

// Check authentication
requireAuth();

// Get data using dbFetchAll
$data = dbFetchAll("SELECT id, name, email, role, status FROM users LIMIT 10");

// Return response
apiResponse(true, 'Data retrieved successfully', $data);
?>