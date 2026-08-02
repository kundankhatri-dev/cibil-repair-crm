<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$category = isset($_GET['category']) ? $_GET['category'] : '';

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

if (!empty($category)) {
    $query = "SELECT * FROM contacts WHERE partner_id = ? AND category = ? ORDER BY name ASC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $partner_id, $category);
} else {
    $query = "SELECT * FROM contacts WHERE partner_id = ? ORDER BY category, name ASC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $partner_id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$contacts = mysqli_fetch_all($result, MYSQLI_ASSOC);

echo json_encode(['success' => true, 'data' => $contacts]);

mysqli_close($conn);
?>