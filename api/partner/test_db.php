<?php
// api/partner/test_db.php

// Only allow access from your IP or with a secret key
$allowed_ips = ['127.0.0.1', '::1', 'YOUR_IP_HERE'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    // Still allow via a secret parameter
    if (!isset($_GET['debug']) || $_GET['debug'] !== 'CIBIL_2024_SECRET') {
        http_response_code(403);
        die('Access Denied');
    }
}

header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Database Configuration Check ===\n\n";

// 1. Check if database.php exists
$db_file = '../../config/database.php';
if (file_exists($db_file)) {
    echo "✅ database.php found at: " . realpath($db_file) . "\n\n";
} else {
    echo "❌ database.php NOT found at: $db_file\n";
    exit;
}

// 2. Load the file
require_once $db_file;

// 3. Check what was defined
echo "=== Functions Defined ===\n";
$functions = get_defined_functions()['user'];
$db_functions = [];
foreach ($functions as $func) {
    if (strpos($func, 'db') !== false || 
        strpos($func, 'DB') !== false || 
        strpos($func, 'connect') !== false ||
        strpos($func, 'mysql') !== false) {
        $db_functions[] = $func;
    }
}

if (count($db_functions) > 0) {
    echo "Found " . count($db_functions) . " database-related functions:\n";
    foreach ($db_functions as $func) {
        echo "  - $func()\n";
    }
} else {
    echo "No database-related functions found.\n";
}
echo "\n";

// 4. Check global variables
echo "=== Global Variables ===\n";
$globals = $GLOBALS;
$db_vars = [];
foreach ($globals as $name => $value) {
    if (strpos($name, 'db') !== false || 
        strpos($name, 'DB') !== false || 
        strpos($name, 'conn') !== false ||
        strpos($name, 'mysql') !== false) {
        $type = gettype($value);
        if ($type === 'object') {
            $type = get_class($value);
        }
        $db_vars[] = "$name = $type";
    }
}

if (count($db_vars) > 0) {
    echo "Found " . count($db_vars) . " database-related variables:\n";
    foreach ($db_vars as $var) {
        echo "  - \$$var\n";
    }
} else {
    echo "No database-related global variables found.\n";
}
echo "\n";

// 5. Check constants
echo "=== Database Constants ===\n";
$constants = get_defined_constants();
$db_constants = [];
foreach ($constants as $name => $value) {
    if (strpos($name, 'DB_') === 0 || 
        strpos($name, 'MYSQL') === 0 || 
        strpos($name, 'DATABASE') !== false) {
        $db_constants[] = "$name = $value";
    }
}

if (count($db_constants) > 0) {
    echo "Found " . count($db_constants) . " database constants:\n";
    foreach ($db_constants as $const) {
        echo "  - $const\n";
    }
} else {
    echo "No database constants found.\n";
}
echo "\n";

echo "=== Environment Variables ===\n";
$env_vars = ['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME', 'DATABASE_URL'];
foreach ($env_vars as $env) {
    $value = getenv($env);
    if ($value !== false) {
        echo "  - $env = " . ($env === 'DB_PASS' ? '***HIDDEN***' : $value) . "\n";
    }
}
echo "\n";

// 6. Try to establish connection using what we found
echo "=== Attempting Connection ===\n";

// Try different connection patterns
$connected = false;

// Pattern 1: Check if $conn exists
if (isset($conn) && $conn instanceof mysqli) {
    echo "✅ Using global \$conn\n";
    $connected = true;
}
// Pattern 2: Check if $db exists
elseif (isset($db) && $db instanceof mysqli) {
    echo "✅ Using global \$db\n";
    $conn = $db;
    $connected = true;
}
// Pattern 3: Check if function exists
elseif (function_exists('getDBConnection')) {
    echo "✅ Using getDBConnection() function\n";
    $conn = getDBConnection();
    if ($conn && $conn instanceof mysqli) {
        $connected = true;
    }
}
// Pattern 4: Check if function exists
elseif (function_exists('getDatabaseConnection')) {
    echo "✅ Using getDatabaseConnection() function\n";
    $conn = getDatabaseConnection();
    if ($conn && $conn instanceof mysqli) {
        $connected = true;
    }
}
// Pattern 5: Try to use constants
elseif (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
    echo "✅ Using constants DB_HOST, DB_USER, DB_PASS, DB_NAME\n";
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $connected = true;
    }
}

if ($connected && $conn) {
    echo "✅ Connection successful!\n";
    echo "Server info: " . $conn->server_info . "\n";
    echo "Database: " . $conn->dbname . "\n";
    
    // Test query
    $result = $conn->query("SELECT 1 as test");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ Test query successful: " . $row['test'] . "\n";
    }
} else {
    echo "❌ Could not establish database connection\n";
    if (isset($conn) && $conn->connect_error) {
        echo "Error: " . $conn->connect_error . "\n";
    }
}

echo "\n=== Done ===\n";
?>