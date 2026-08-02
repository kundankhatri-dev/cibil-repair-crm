<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

if (!isset($_FILES['poster']) || empty($_FILES['poster']['name'][0])) {
    echo json_encode(['success' => false, 'error' => 'No files uploaded']);
    exit;
}

$upload_dir = __DIR__ . '/../uploads/posters/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$uploaded = [];
$errors = [];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_file_size = 5 * 1024 * 1024;

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS posters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255),
    original_name VARCHAR(255),
    file_path VARCHAR(500),
    file_size INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

foreach ($_FILES['poster']['tmp_name'] as $key => $tmp_name) {
    $original_name = $_FILES['poster']['name'][$key];
    $file_size = $_FILES['poster']['size'][$key];
    $file_error = $_FILES['poster']['error'][$key];
    $file_type = $_FILES['poster']['type'][$key];
    
    if ($file_error !== UPLOAD_ERR_OK) {
        $errors[] = $original_name . ': Upload error';
        continue;
    }
    
    if (!in_array($file_type, $allowed_types)) {
        $errors[] = $original_name . ': Invalid file type';
        continue;
    }
    
    if ($file_size > $max_file_size) {
        $errors[] = $original_name . ': File too large (max 5MB)';
        continue;
    }
    
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $file_path = 'uploads/posters/' . $filename;
    $full_path = __DIR__ . '/../' . $file_path;
    
    if (move_uploaded_file($tmp_name, $full_path)) {
        $query = "INSERT INTO posters (filename, original_name, file_path, file_size) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'sssi', $filename, $original_name, $file_path, $file_size);
        
        if (mysqli_stmt_execute($stmt)) {
            $id = mysqli_insert_id($conn);
            $uploaded[] = [
                'id' => $id,
                'filename' => $filename,
                'original_name' => $original_name,
                'file_path' => '/' . $file_path,
                'file_size' => $file_size
            ];
        } else {
            $errors[] = $original_name . ': Database error';
            @unlink($full_path);
        }
        mysqli_stmt_close($stmt);
    } else {
        $errors[] = $original_name . ': Failed to move file';
    }
}

mysqli_close($conn);

if (count($uploaded) > 0) {
    echo json_encode([
        'success' => true,
        'message' => count($uploaded) . ' poster(s) uploaded',
        'data' => $uploaded,
        'errors' => $errors
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'No files uploaded: ' . implode(', ', $errors)
    ]);
}
exit;
?>