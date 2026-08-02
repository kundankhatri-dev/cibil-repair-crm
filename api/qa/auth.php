<?php
// ============================================================
// CENTRAL AUTHENTICATION & AUTHORIZATION SYSTEM
// ============================================================

session_start();
require_once 'config/database.php';

class Auth {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
    }
    
    // Get current user
    public function currentUser() {
        if (!$this->isLoggedIn()) return null;
        
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'],
            'role_id' => $_SESSION['role_id'] ?? null,
            'permissions' => $_SESSION['permissions'] ?? []
        ];
    }
    
    // Check if user has specific permission
    public function hasPermission($permission_key) {
        if (!isset($_SESSION['permissions'])) return false;
        
        // Super admin has all permissions
        if ($_SESSION['user_role'] === 'super_admin') return true;
        
        return in_array($permission_key, $_SESSION['permissions']);
    }
    
    // Check if user has any of the allowed roles
    public function hasRole($allowed_roles) {
        if (!is_array($allowed_roles)) {
            $allowed_roles = [$allowed_roles];
        }
        
        return in_array($_SESSION['user_role'], $allowed_roles);
    }
    
    // Get dashboard access based on role
    public function getAccessibleDashboards() {
        $role = $_SESSION['user_role'];
        
        $dashboardAccess = [
            'super_admin' => [
                'admin', 'ceo', 'hr', 'finance', 'sales', 'marketing',
                'support', 'risk', 'project', 'training', 'document',
                'qa', 'it', 'legal', 'operations', 'dispute', 'credit_analyst'
            ],
            'admin' => [
                'admin', 'hr', 'finance', 'sales', 'support', 'document', 'it'
            ],
            'ceo' => ['ceo', 'sales', 'finance', 'hr', 'operations'],
            'hr_manager' => ['hr', 'training', 'employee'],
            'finance_manager' => ['finance', 'sales'],
            'sales_manager' => ['sales', 'marketing'],
            'support_manager' => ['support', 'customer_support'],
            'support_agent' => ['support', 'customer_support'],
            'credit_analyst' => ['credit_analyst', 'dispute_processing'],
            'risk_manager' => ['risk', 'compliance'],
            'compliance_officer' => ['legal_compliance', 'risk'],
            'project_manager' => ['project_management'],
            'qa_manager' => ['quality_assurance'],
            'it_admin' => ['it'],
            'marketing_manager' => ['marketing'],
            'client' => ['client'],
            'partner' => ['partner'],
            'employee' => ['employee'],
            'trainer' => ['training']
        ];
        
        return $dashboardAccess[$role] ?? ['client'];
    }
    
    // Get redirect URL based on role
    public function getDefaultDashboard() {
        $role = $_SESSION['user_role'];
        
        $dashboardMap = [
            'super_admin' => 'admin_dashboard.php',
            'admin' => 'admin_dashboard.php',
            'ceo' => 'ceo_dashboard.php',
            'hr_manager' => 'hr_dashboard.php',
            'finance_manager' => 'finance_dashboard.php',
            'sales_manager' => 'sales_dashboard.php',
            'support_manager' => 'support_dashboard.php',
            'support_agent' => 'support_dashboard.php',
            'credit_analyst' => 'credit_analyst_dashboard.php',
            'risk_manager' => 'risk_dashboard.php',
            'compliance_officer' => 'legal_dashboard.php',
            'project_manager' => 'project_dashboard.php',
            'qa_manager' => 'qa_dashboard.php',
            'it_admin' => 'it_dashboard.php',
            'marketing_manager' => 'marketing_dashboard.php',
            'client' => 'client_dashboard.php',
            'partner' => 'partner_dashboard.php',
            'employee' => 'employee_dashboard.php',
            'trainer' => 'training_dashboard.php'
        ];
        
        return $dashboardMap[$role] ?? 'login.php';
    }
    
    // Authenticate user
    public function authenticate($email, $password) {
        $email = mysqli_real_escape_string($this->conn, $email);
        
        $query = "SELECT u.*, r.role_name, r.id as role_id 
                  FROM users u
                  LEFT JOIN system_roles r ON u.role = r.role_name
                  WHERE u.email = '$email' AND u.status = 'active'";
        
        $result = mysqli_query($this->conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password (assuming password_hash is used)
            if (password_verify($password, $user['password'])) {
                $this->setUserSession($user);
                return true;
            }
        }
        
        return false;
    }
    
    // Set user session
    private function setUserSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['role_id'] = $user['role_id'];
        
        // Load user permissions
        $_SESSION['permissions'] = $this->loadUserPermissions($user['id']);
        
        // Regenerate session ID for security
        session_regenerate_id(true);
    }
    
    // Load user permissions
    private function loadUserPermissions($user_id) {
        $query = "SELECT p.permission_key 
                  FROM system_role_permissions rp
                  JOIN system_permissions p ON rp.permission_id = p.id
                  JOIN users u ON u.role_id = rp.role_id
                  WHERE u.id = $user_id";
        
        $result = mysqli_query($this->conn, $query);
        $permissions = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $permissions[] = $row['permission_key'];
        }
        
        return $permissions;
    }
    
    // Logout
    public function logout() {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
}

// Initialize Auth
$auth = new Auth($conn);
?>