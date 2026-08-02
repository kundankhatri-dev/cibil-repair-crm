<?php
include 'config/database.php';
$data = json_decode(file_get_contents('php://input'), true);

$name = mysqli_real_escape_string($conn, $data['name'] ?? '');
$location = mysqli_real_escape_string($conn, $data['location'] ?? '');
$owner = mysqli_real_escape_string($conn, $data['owner'] ?? '');
$phone = mysqli_real_escape_string($conn, $data['phone'] ?? '');

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Franchisee name required']);
    exit;
}

$query = "INSERT INTO franchisees (name, location, owner, phone) VALUES ('$name', '$location', '$owner', '$phone')";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>