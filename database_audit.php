<?php
// ============================================================
// SIMPLE DATABASE AUDIT - DEBUG VERSION
// ============================================================

// Show ALL errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// ============================================================
// SECURITY CHECK
// ============================================================
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please <a href='login.php'>login</a> first.");
}

// Check if user is admin
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    die("Access Denied. <a href='admin-dashboard.php'>Back to Dashboard</a>");
}

// ============================================================
// DATABASE CONNECTION - Using your credentials
// ============================================================
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("❌ Database Connection Failed: " . $e->getMessage());
}

echo "✅ Database connected successfully!<br><br>";

// ============================================================
// GET ALL TABLES
// ============================================================
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>📊 Database: $dbname</h2>";
echo "<p>Total Tables: " . count($tables) . "</p>";
echo "<hr>";

$grandTotal = 0;

// ============================================================
// DISPLAY EACH TABLE
// ============================================================
foreach ($tables as $table) {
    try {
        // Count records
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        $grandTotal += $count;
        
        echo "<h3>📋 Table: $table</h3>";
        echo "<p>Records: <strong>" . number_format($count) . "</strong></p>";
        
        if ($count > 0) {
            // Get column names
            $cols = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_COLUMN);
            
            // Get last 10 records
            $rows = $pdo->query("SELECT * FROM `$table` ORDER BY id DESC LIMIT 10")->fetchAll();
            
            if (!empty($rows)) {
                echo "<table border='1' cellpadding='5' style='border-collapse:collapse;margin-bottom:20px;'>";
                echo "<tr style='background:#0d9e78;color:white;'>";
                foreach ($cols as $col) {
                    echo "<th>" . htmlspecialchars($col) . "</th>";
                }
                echo "</tr>";
                
                foreach ($rows as $row) {
                    echo "<tr>";
                    foreach ($cols as $col) {
                        $val = $row[$col] ?? 'NULL';
                        if (is_string($val) && strlen($val) > 50) {
                            $val = substr($val, 0, 50) . '…';
                        }
                        echo "<td>" . htmlspecialchars($val) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
                if ($count > 10) {
                    echo "<p style='color:#666;'>Showing last 10 of $count records</p>";
                }
            }
        } else {
            echo "<p style='color:#999;'>No records found</p>";
        }
        echo "<hr>";
        
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error on table $table: " . $e->getMessage() . "</p>";
    }
}

// ============================================================
// SUMMARY
// ============================================================
echo "<h2>📈 Summary</h2>";
echo "<p><strong>Total Records Across All Tables: " . number_format($grandTotal) . "</strong></p>";
echo "<p>Generated: " . date('Y-m-d H:i:s') . "</p>";
echo "<p><a href='admin-dashboard.php'>← Back to Dashboard</a></p>";

// ============================================================
// SELF-DELETE OPTION
// ============================================================
if (isset($_GET['delete'])) {
    if (unlink(__FILE__)) {
        echo "<p style='color:green;font-weight:bold;'>✅ File deleted for security!</p>";
    } else {
        echo "<p style='color:red;'>❌ Could not delete. Please delete manually.</p>";
    }
} else {
    echo "<p><a href='?delete=1' onclick=\"return confirm('Delete this file permanently?')\" style='color:red;'>🗑️ Delete this file after use</a></p>";
}
?>