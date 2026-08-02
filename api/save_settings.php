<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Create table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category VARCHAR(50) NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$saved = 0;

// Check if it's single setting
if (isset($input['category']) && isset($input['key'])) {
    $category = $input['category'];
    $key = $input['key'];
    $value = $input['value'] ?? '';
    
    $check = mysqli_query($conn, "SELECT id FROM settings WHERE category = '$category' AND setting_key = '$key'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE settings SET setting_value = '$value' WHERE category = '$category' AND setting_key = '$key'");
    } else {
        mysqli_query($conn, "INSERT INTO settings (category, setting_key, setting_value) VALUES ('$category', '$key', '$value')");
    }
    $saved = 1;
    echo json_encode(['success' => true, 'message' => 'Setting saved']);
    exit;
}

// Check if it's multiple settings
if (isset($input['settings']) && is_array($input['settings'])) {
    foreach ($input['settings'] as $setting) {
        $category = $setting['category'] ?? 'general';
        $key = $setting['key'] ?? '';
        $value = $setting['value'] ?? '';
        
        if (empty($key)) continue;
        
        $check = mysqli_query($conn, "SELECT id FROM settings WHERE category = '$category' AND setting_key = '$key'");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE settings SET setting_value = '$value' WHERE category = '$category' AND setting_key = '$key'");
        } else {
            mysqli_query($conn, "INSERT INTO settings (category, setting_key, setting_value) VALUES ('$category', '$key', '$value'");
        }
        $saved++;
    }
    echo json_encode(['success' => true, 'message' => $saved . ' settings saved']);
    exit;
}

// Flat format
foreach ($input as $key => $value) {
    $category = 'general';
    $check = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key = '$key'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE settings SET setting_value = '$value' WHERE setting_key = '$key'");
    } else {
        mysqli_query($conn, "INSERT INTO settings (category, setting_key, setting_value) VALUES ('$category', '$key', '$value')");
    }
    $saved++;
}

echo json_encode(['success' => true, 'message' => $saved . ' settings saved']);

mysqli_close($conn);
exit;
?>