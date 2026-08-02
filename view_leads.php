<?php
session_start();

// Simple password protection - change 'admin123' to a strong password
$valid_username = 'admin';
$valid_password = 'admin123';

if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != $valid_username || $_SERVER['PHP_AUTH_PW'] != $valid_password) {
    header('WWW-Authenticate: Basic realm="Lead Manager"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Access denied. Please provide valid credentials.';
    exit;
}

// Include database configuration
include 'config/database.php';

// Fetch all leads, newest first
$result = $conn->query("SELECT * FROM leads ORDER BY id DESC");
if (!$result) {
    die('Error fetching leads: ' . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #1f8a72; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #1f8a72; color: white; font-weight: 600; }
        tr:hover { background: #f5f5f5; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .footer { margin-top: 20px; text-align: center; color: #666; }
        @media (max-width: 768px) {
            th, td { padding: 8px 10px; font-size: 12px; }
            table { font-size: 12px; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>📋 Submitted Leads</h1>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Message</th>
                    <th>Aadhar</th>
                    <th>PAN</th>
                    <th>Submitted At</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td><?= htmlspecialchars($row['email'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['message'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['aadhar'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['pan'] ?? '-') ?></td>
                    <td><?= $row['created_at'] ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if ($result->num_rows === 0): ?>
                <tr><td colspan="8" style="text-align:center;">No leads found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="footer">
        <p>Total leads: <?= $result->num_rows ?></p>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>