<?php
// ============================================================
// MAIN AUTHENTICATION HANDLER - Integrates with your database.php
// ============================================================

require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['logged_in']) && 
               $_SESSION['logged_in'] === true &&
               isset($_SESSION['last_activity']) && 
               (time() - $_SESSION['last_activity']) < 3600;
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
            'permissions' => $this->getUserPermissions($_SESSION['user_id'])
        ];
    }
    
    // Get user permissions from database
    public function getUserPermissions($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.permission_key 
                FROM system_role_permissions rp
                JOIN system_permissions p ON rp.permission_id = p.id
                JOIN users u ON u.role_id = rp.role_id
                WHERE u.id = ?
            ");
            $stmt->execute([$user_id]);
            
            $permissions = [];
            while ($row = $stmt->fetch()) {
                $permissions[] = $row['permission_key'];
            }
            return $permissions;
        } catch(PDOException $e) {
            error_log('Error loading permissions: ' . $e->getMessage());
            return [];
        }
    }
    
    // Check if user has permission
    public function hasPermission($permission_key) {
        if ($_SESSION['user_role'] === 'super_admin') return true;
        
        return in_array($permission_key, $_SESSION['permissions'] ?? []);
    }
    
    // Check if user has any of allowed roles
    public function hasRole($allowed_roles) {
        if (!is_array($allowed_roles)) {
            $allowed_roles = [$allowed_roles];
        }
        
        return in_array($_SESSION['user_role'], $allowed_roles);
    }
    
    // Get dashboard access mapping based on role
    public function getAccessibleDashboards() {
        $role = $_SESSION['user_role'];
        
        $dashboardAccess = [
            'super_admin' => [
                'admin', 'ceo', 'hr', 'finance', 'sales', 'marketing',
                'support', 'customer_support', 'risk', 'project_management', 
                'training', 'document', 'quality_assurance', 'it', 
                'legal_compliance', 'operations', 'dispute_processing', 
                'credit_analyst', 'client', 'partner', 'employee'
            ],
            'admin' => [
                'admin', 'hr', 'finance', 'sales', 'support', 
                'document', 'it', 'operations'
            ],
            'ceo' => ['ceo', 'sales', 'finance', 'hr', 'operations', 'risk'],
            'hr_manager' => ['hr', 'training', 'employee'],
            'finance_manager' => ['finance', 'sales'],
            'sales_manager' => ['sales', 'marketing'],
            'support_manager' => ['support', 'customer_support'],
            'support_agent' => ['support', 'customer_support'],
            'credit_analyst' => ['credit_analyst', 'dispute_processing'],
            'risk_manager' => ['risk', 'legal_compliance'],
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
    
    // Get default dashboard for user role
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
        
        return $dashboardMap[$role] ?? 'login.html';
    }
    
    // Authenticate user
    public function authenticate($email, $password) {
        try {
            // Check rate limit
            if (!checkRateLimit('login', 5, 300)) {
                $_SESSION['login_error'] = 'Too many login attempts. Please try again later.';
                return false;
            }
            
            $stmt = $this->db->prepare("
                SELECT u.*, r.role_name, r.id as role_id 
                FROM users u
                LEFT JOIN system_roles r ON u.role = r.role_name
                WHERE u.email = ? AND u.status = 'active'
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $this->setUserSession($user);
                logActivity($user['id'], 'login', 'User logged in successfully');
                return true;
            }
            
            logActivity(0, 'login_failed', "Failed login attempt for email: $email");
            return false;
        } catch(PDOException $e) {
            error_log('Authentication error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Set user session
    private function setUserSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['permissions'] = $this->getUserPermissions($user['id']);
        
        // Regenerate session ID
        session_regenerate_id(true);
    }
    
    // Logout
    public function logout() {
        if ($this->isLoggedIn()) {
            logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        
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
$auth = new Auth();
?>