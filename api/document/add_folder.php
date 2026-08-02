<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$folder_name = $data['folder_name'] ?? '';
$parent_id = $data['parent_id'] ?? null;
$access_level = $data['access_level'] ?? 'private';
$created_by = $data['created_by'] ?? 1;

if (!$folder_name) {
    echo json_encode(['success' => false, 'error' => 'Folder name required']);
    exit;
}

$parent_id_val = $parent_id ? $parent_id : 'NULL';
$folder_path = $parent_id ? "/$folder_name" : "/$folder_name";

$query = "INSERT INTO dm_folders (folder_name, parent_id, folder_path, access_level, created_by) 
          VALUES ('$folder_name', $parent_id_val, '$folder_path', '$access_level', $created_by)";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Folder created successfully', 'id' => mysqli_insert_id($conn)]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>