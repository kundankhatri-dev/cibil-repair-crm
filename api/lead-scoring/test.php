<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: PHP is working<br>";

require_once __DIR__ . '/config.php';
echo "Step 2: Config loaded<br>";

$conn = getConnection();
echo "Step 3: Got connection<br>";

if ($conn) {
    echo "Step 4: Connection successful<br>";
} else {
    echo "Step 4: Connection failed<br>";
}
?>