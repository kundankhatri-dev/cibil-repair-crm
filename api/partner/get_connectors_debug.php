<?php
// ============================================================
// DEBUG: Partner Get Connectors
// ============================================================

// Turn on error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }

    $partner_id = (int)$_SESSION['user_id'];

    $conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

    if (!$conn) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }

    mysqli_set_charset($conn, 'utf8mb4');

    // ========== CHECK IF TABLE EXISTS ==========
    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'connectors'");
    $tableExists = mysqli_num_rows($tableCheck) > 0;

    if (!$tableExists) {
        // Create table
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS connectors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            partner_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            email VARCHAR(100) DEFAULT NULL,
            type VARCHAR(50) DEFAULT 'other',
            company VARCHAR(100) DEFAULT NULL,
            city VARCHAR(100) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            leads_referred INT DEFAULT 0,
            commission_due DECIMAL(10,2) DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_partner_id (partner_id)
        )");
    }

    // ========== GET CONNECTORS ==========
    $query = "SELECT id, name, phone, email, type, company, city, notes, 
                     leads_referred, commission_due, status, created_at 
              FROM connectors 
              WHERE partner_id = $partner_id 
              ORDER BY created_at DESC";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Query failed: ' . mysqli_error($conn)]);
        mysqli_close($conn);
        exit;
    }

    $connectors = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $connectors[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'] ?? '—',
            'phone' => $row['phone'] ?? '—',
            'email' => $row['email'] ?? '—',
            'type' => $row['type'] ?? 'other',
            'company' => $row['company'] ?? '—',
            'city' => $row['city'] ?? '—',
            'notes' => $row['notes'] ?? '',
            'leads_referred' => (int)($row['leads_referred'] ?? 0),
            'commission_due' => (float)($row['commission_due'] ?? 0),
            'status' => $row['status'] ?? 'active',
            'created_at' => $row['created_at'] ?? ''
        ];
    }

    echo json_encode([
        'success' => true,
        'connectors' => $connectors,
        'total' => count($connectors)
    ]);

    mysqli_close($conn);

} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>