<?php
// Working cron file - api/cron/cron_working.php
echo "=== CIBIL Repair Follow-up Cron Job ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Database connection
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    die("❌ Database connection failed: " . mysqli_connect_error());
}

echo "✅ Database connected successfully\n";

// Check if followups table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'followups'");
if (mysqli_num_rows($table_check) > 0) {
    echo "✅ Followups table exists\n";
    
    $query = "SELECT COUNT(*) as count FROM followups WHERE status = 'pending' AND reminder_sent = 0";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    
    echo "📋 Pending follow-ups needing reminders: " . $row['count'] . "\n";
    
    // Update reminder_sent for testing (optional)
    // mysqli_query($conn, "UPDATE followups SET reminder_sent = 1 WHERE status = 'pending' AND reminder_sent = 0");
    
} else {
    echo "⚠️ Followups table does not exist yet\n";
    echo "   You need to create the followups table first\n";
}

echo "\n✅ Cron job completed at: " . date('Y-m-d H:i:s') . "\n";
mysqli_close($conn);
?>