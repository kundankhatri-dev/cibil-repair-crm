<?php
// api/hr/add_holiday.php - Add new holiday
session_start();
header('Content-Type: application/json');

// Allow only HR or Admin
$allowed_roles = ['hr', 'admin'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

$holiday_name = trim($input['name'] ?? '');
$holiday_date = trim($input['date'] ?? '');
$holiday_type = trim($input['type'] ?? 'public');
$description = trim($input['description'] ?? '');

// Validation
$errors = [];

if (empty($holiday_name)) {
    $errors[] = "Holiday name is required";
} elseif (strlen($holiday_name) < 3) {
    $errors[] = "Holiday name must be at least 3 characters";
} elseif (strlen($holiday_name) > 100) {
    $errors[] = "Holiday name must be less than 100 characters";
}

if (empty($holiday_date)) {
    $errors[] = "Holiday date is required";
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $holiday_date)) {
    $errors[] = "Invalid date format. Use YYYY-MM-DD";
} else {
    // Check if date is in the past
    if (strtotime($holiday_date) < strtotime(date('Y-m-d'))) {
        $errors[] = "Cannot add holiday for a past date";
    }
}

// Validate holiday type
$valid_types = ['public', 'company', 'festival', 'national'];
if (!in_array($holiday_type, $valid_types)) {
    $errors[] = "Invalid holiday type";
}

// Check for duplicate holiday on same date
$check_query = "SELECT id, holiday_name FROM holidays WHERE holiday_date = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "s", $holiday_date);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$existing = mysqli_fetch_assoc($check_result);
mysqli_stmt_close($check_stmt);

if ($existing) {
    $errors[] = "A holiday already exists on this date: " . $existing['holiday_name'];
}

// Return errors if any
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Get year from date
$year = date('Y', strtotime($holiday_date));

// Insert holiday
$insert_query = "INSERT INTO holidays (holiday_name, holiday_date, holiday_type, description, year, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())";

$insert_stmt = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($insert_stmt, "ssssi", $holiday_name, $holiday_date, $holiday_type, $description, $year);
$inserted = mysqli_stmt_execute($insert_stmt);
$holiday_id = mysqli_insert_id($conn);
mysqli_stmt_close($insert_stmt);

if ($inserted) {
    // Log activity
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $log_query = "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address) 
                  VALUES (?, 'holiday_added', ?, ?)";
    $log_stmt = mysqli_prepare($conn, $log_query);
    $description_log = "Added holiday: $holiday_name on " . date('d M Y', strtotime($holiday_date));
    mysqli_stmt_bind_param($log_stmt, "iss", $_SESSION['user_id'], $description_log, $ip_address);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
    
    // Get day name
    $day_name = date('l', strtotime($holiday_date));
    $date_formatted = date('d M Y', strtotime($holiday_date));
    
    $type_labels = [
        'public' => 'Public Holiday',
        'company' => 'Company Holiday',
        'festival' => 'Festival',
        'national' => 'National Holiday'
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Holiday added successfully',
        'holiday' => [
            'id' => $holiday_id,
            'name' => $holiday_name,
            'date' => $holiday_date,
            'date_formatted' => $date_formatted,
            'day_name' => $day_name,
            'type' => $holiday_type,
            'type_label' => $type_labels[$holiday_type],
            'description' => $description,
            'year' => $year
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add holiday']);
}

mysqli_close($conn);
?>