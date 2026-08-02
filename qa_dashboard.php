<?php
session_start();

$allowed_roles = ["qa_manager,admin,super_admin"];
if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["user_role"], $allowed_roles)) {
    header("Location: login.php");
    exit;
}

$user_name = $_SESSION["user_name"] ?? "User";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quality Assurance Dashboard</h1>
            <div>
                <span style="margin-right: 15px;">👋 </span>
                <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-chart-line" style="font-size: 24px;"></i>
                <div class="stat-value">—</div>
                <div>Coming Soon</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-users" style="font-size: 24px;"></i>
                <div class="stat-value">—</div>
                <div>Coming Soon</div>
            </div>
        </div>
        <div class="card">
            <div class="card-title">Welcome to Quality Assurance Dashboard</div>
            <p>This dashboard is ready for you. Content will be added based on your requirements.</p>
            <p style="margin-top: 12px; color: #6b7280;">Role: </p>
        </div>
    </main>
</div>
</body>
</html>