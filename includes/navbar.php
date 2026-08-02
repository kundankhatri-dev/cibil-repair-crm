<?php
// ============================================================
// ENHANCED UNIFIED NAVIGATION MENU - WITH ROLE-BASED VISIBILITY
// ============================================================

// Get current page name for active highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['user_role'] ?? 'guest';
$user_name = $_SESSION['user_name'] ?? 'User';

// Define role-based menu visibility
$role_menus = [
    'super_admin' => [
        'dashboard' => ['admin-dashboard.php', 'ceo-dashboard.php', 'management-dashboard.php'],
        'analytics' => ['ceo-dashboard.php', 'management-dashboard.php', 'finance-dashboard.php'],
        'teams' => ['lead-management-dashboard.php', 'credit-analyst-dashboard.php', 'dispute-processing-dashboard.php', 'loan-assistance-dashboard.php'],
        'support' => ['customer-support-dashboard.php', 'operations-dashboard.php'],
        'legal' => ['legal-compliance-dashboard.php', 'hr-dashboard.php']
    ],
    'admin' => [
        'dashboard' => ['admin-dashboard.php'],
        'analytics' => ['finance-dashboard.php', 'management-dashboard.php'],
        'teams' => ['lead-management-dashboard.php', 'credit-analyst-dashboard.php'],
        'support' => ['customer-support-dashboard.php', 'operations-dashboard.php'],
        'legal' => ['legal-compliance-dashboard.php', 'hr-dashboard.php']
    ],
    'ceo' => [
        'dashboard' => ['ceo-dashboard.php'],
        'analytics' => ['ceo-dashboard.php', 'finance-dashboard.php'],
        'teams' => [],
        'support' => [],
        'legal' => []
    ],
    'hr_manager' => [
        'dashboard' => ['hr-dashboard.php'],
        'analytics' => ['hr-dashboard.php'],
        'teams' => [],
        'support' => [],
        'legal' => ['hr-dashboard.php']
    ],
    'finance_manager' => [
        'dashboard' => ['finance-dashboard.php'],
        'analytics' => ['finance-dashboard.php'],
        'teams' => [],
        'support' => [],
        'legal' => []
    ],
    'sales_manager' => [
        'dashboard' => ['lead-management-dashboard.php'],
        'analytics' => ['lead-management-dashboard.php'],
        'teams' => ['lead-management-dashboard.php'],
        'support' => [],
        'legal' => []
    ],
    'support_manager' => [
        'dashboard' => ['customer-support-dashboard.php'],
        'analytics' => ['customer-support-dashboard.php', 'operations-dashboard.php'],
        'teams' => [],
        'support' => ['customer-support-dashboard.php', 'operations-dashboard.php'],
        'legal' => []
    ],
    'credit_analyst' => [
        'dashboard' => ['credit-analyst-dashboard.php'],
        'analytics' => ['credit-analyst-dashboard.php'],
        'teams' => ['credit-analyst-dashboard.php'],
        'support' => [],
        'legal' => []
    ],
    'client' => [
        'dashboard' => ['client-dashboard.php'],
        'analytics' => [],
        'teams' => [],
        'support' => ['customer-support-dashboard.php'],
        'legal' => []
    ],
    'partner' => [
        'dashboard' => ['partner-dashboard.php'],
        'analytics' => [],
        'teams' => [],
        'support' => [],
        'legal' => []
    ],
    'employee' => [
        'dashboard' => ['employee-dashboard.php'],
        'analytics' => [],
        'teams' => [],
        'support' => [],
        'legal' => []
    ]
];

// Function to check if menu item should be visible
function showMenuItem($page, $user_role, $role_menus) {
    if ($user_role === 'super_admin') return true;
    
    foreach ($role_menus[$user_role] ?? [] as $category => $pages) {
        if (in_array($page, $pages)) return true;
    }
    return false;
}

// Get role display name
$role_display = [
    'super_admin' => 'Super Admin',
    'admin' => 'Administrator',
    'ceo' => 'CEO',
    'hr_manager' => 'HR Manager',
    'finance_manager' => 'Finance Manager',
    'sales_manager' => 'Sales Manager',
    'support_manager' => 'Support Manager',
    'credit_analyst' => 'Credit Analyst',
    'client' => 'Client',
    'partner' => 'Partner',
    'employee' => 'Employee'
];
$role_name = $role_display[$user_role] ?? ucfirst(str_replace('_', ' ', $user_role));
?>

<!DOCTYPE html>
<html>
<head>
<style>
/* ============================================================
   UNIFIED NAVIGATION BAR STYLES
   ============================================================ */
:root {
    --nav-bg: #0b2a23;
    --nav-hover: rgba(255,255,255,0.08);
    --nav-active: rgba(13,158,120,0.25);
    --nav-text: rgba(255,255,255,0.75);
    --nav-text-active: #ffffff;
    --brand: #0d9e78;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

/* Top Navigation Bar */
.top-nav {
    background: var(--nav-bg);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.nav-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    height: 60px;
}

/* Logo */
.nav-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.nav-logo-icon {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, var(--brand), #06b6d4);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    color: white;
}

.nav-logo-text {
    font-family: 'Montserrat', sans-serif;
    font-weight: 700;
    color: white;
    font-size: 16px;
}

.nav-logo-text span {
    color: var(--brand);
}

/* Desktop Menu */
.nav-menu {
    display: flex;
    align-items: center;
    gap: 8px;
    list-style: none;
}

.nav-item {
    position: relative;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    color: var(--nav-text);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.2s ease;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.nav-link:hover {
    background: var(--nav-hover);
    color: var(--nav-text-active);
}

.nav-link.active {
    background: var(--nav-active);
    color: var(--nav-text-active);
}

.nav-link i {
    font-size: 14px;
    width: 18px;
}

/* Dropdown Menu */
.nav-dropdown {
    position: relative;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    background: var(--nav-bg);
    min-width: 220px;
    border-radius: 8px;
    padding: 8px 0;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease;
    z-index: 1001;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    border: 1px solid rgba(255,255,255,0.1);
}

.nav-dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    color: var(--nav-text);
    text-decoration: none;
    font-size: 12px;
    transition: all 0.2s ease;
}

.dropdown-menu a:hover {
    background: var(--nav-hover);
    color: var(--nav-text-active);
}

.dropdown-menu a i {
    width: 20px;
    font-size: 13px;
}

.dropdown-divider {
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 6px 0;
}

/* User Section */
.nav-user {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--nav-text);
}

.user-avatar {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, var(--brand), #06b6d4);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 12px;
    color: white;
}

.user-name {
    font-size: 13px;
    font-weight: 500;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    background: rgba(13,158,120,0.15);
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
    color: var(--brand);
    margin-left: 8px;
}

/* LOGOUT BUTTON - THIS IS WHAT YOU NEED */
.logout-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: rgba(239,68,68,0.15);
    border: 1px solid rgba(239,68,68,0.3);
    border-radius: 8px;
    color: #fca5a5;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.logout-btn:hover {
    background: #dc2626;
    color: white;
    border-color: #dc2626;
}

/* Mobile Toggle */
.mobile-toggle {
    display: none;
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    padding: 8px;
}

/* Responsive */
@media (max-width: 1024px) {
    .nav-menu {
        display: none;
    }
    .mobile-toggle {
        display: block;
    }
    .user-name {
        display: none;
    }
}

/* Main content offset */
.dashboard-content {
    margin-top: 60px;
    padding: 24px;
    min-height: calc(100vh - 60px);
}
</style>
</head>
<body>

<!-- Top Navigation Bar -->
<div class="top-nav">
    <div class="nav-container">
        <!-- Logo -->
        <a href="admin-dashboard.php" class="nav-logo">
            <div class="nav-logo-icon">CR</div>
            <div class="nav-logo-text">CIBIL<span>Repair</span></div>
        </a>

        <!-- Desktop Menu - Role Based -->
        <ul class="nav-menu">
            <!-- Dashboard Menu -->
            <?php if (!empty($role_menus[$user_role]['dashboard'] ?? [])): ?>
            <li class="nav-dropdown">
                <a href="#" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard <i class="fas fa-chevron-down" style="font-size: 10px;"></i></a>
                <div class="dropdown-menu">
                    <?php foreach ($role_menus[$user_role]['dashboard'] as $dashboard): ?>
                        <?php if (file_exists($dashboard)): ?>
                        <a href="<?= $dashboard ?>"><i class="fas fa-chart-line"></i> <?= ucfirst(str_replace('-dashboard.php', '', $dashboard)) ?> Dashboard</a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </li>
            <?php endif; ?>

            <!-- Analytics Menu -->
            <?php if (!empty($role_menus[$user_role]['analytics'] ?? [])): ?>
            <li class="nav-dropdown">
                <a href="#" class="nav-link"><i class="fas fa-chart-line"></i> Analytics <i class="fas fa-chevron-down" style="font-size: 10px;"></i></a>
                <div class="dropdown-menu">
                    <?php foreach ($role_menus[$user_role]['analytics'] as $analytics): ?>
                        <a href="<?= $analytics ?>"><i class="fas fa-chart-bar"></i> <?= ucfirst(str_replace('-dashboard.php', '', $analytics)) ?></a>
                    <?php endforeach; ?>
                </div>
            </li>
            <?php endif; ?>

            <!-- Teams Menu -->
            <?php if (!empty($role_menus[$user_role]['teams'] ?? [])): ?>
            <li class="nav-dropdown">
                <a href="#" class="nav-link"><i class="fas fa-users"></i> Teams <i class="fas fa-chevron-down" style="font-size: 10px;"></i></a>
                <div class="dropdown-menu">
                    <?php foreach ($role_menus[$user_role]['teams'] as $team): ?>
                        <a href="<?= $team ?>"><i class="fas fa-user-friends"></i> <?= ucfirst(str_replace('-dashboard.php', '', $team)) ?></a>
                    <?php endforeach; ?>
                </div>
            </li>
            <?php endif; ?>

            <!-- Support Menu -->
            <?php if (!empty($role_menus[$user_role]['support'] ?? [])): ?>
            <li class="nav-dropdown">
                <a href="#" class="nav-link"><i class="fas fa-headset"></i> Support <i class="fas fa-chevron-down" style="font-size: 10px;"></i></a>
                <div class="dropdown-menu">
                    <?php foreach ($role_menus[$user_role]['support'] as $support): ?>
                        <a href="<?= $support ?>"><i class="fas fa-ticket-alt"></i> <?= ucfirst(str_replace('-dashboard.php', '', $support)) ?></a>
                    <?php endforeach; ?>
                </div>
            </li>
            <?php endif; ?>

            <!-- Legal Menu -->
            <?php if (!empty($role_menus[$user_role]['legal'] ?? [])): ?>
            <li class="nav-dropdown">
                <a href="#" class="nav-link"><i class="fas fa-gavel"></i> Legal <i class="fas fa-chevron-down" style="font-size: 10px;"></i></a>
                <div class="dropdown-menu">
                    <?php foreach ($role_menus[$user_role]['legal'] as $legal): ?>
                        <a href="<?= $legal ?>"><i class="fas fa-balance-scale"></i> <?= ucfirst(str_replace('-dashboard.php', '', $legal)) ?></a>
                    <?php endforeach; ?>
                </div>
            </li>
            <?php endif; ?>
        </ul>

        <!-- User Section with LOGOUT BUTTON -->
        <div class="nav-user">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
                <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
                <span class="role-badge"><i class="fas fa-crown"></i> <?= htmlspecialchars($role_name) ?></span>
            </div>
            <!-- THIS IS THE LOGOUT BUTTON -->
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <!-- Mobile Toggle -->
        <button class="mobile-toggle" id="mobileToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</div>

<script>
// Mobile menu toggle
document.getElementById('mobileToggle').addEventListener('click', function() {
    const menu = document.querySelector('.nav-menu');
    if (menu) {
        menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
    }
});
</script>

</body>
</html>