<?php
$content = '<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");

session_start();

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

$conn = mysqli_connect("localhost", "u929623538_cibilrepair", "Kundanlaxmi@1995", "u929623538_cibil");

if (!$conn) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
    mysqli_close($conn);
    exit;
}

$id = isset($input["id"]) ? intval($input["id"]) : 0;
$clientId = isset($input["client_id"]) ? intval($input["client_id"]) : 0;
$documentName = isset($input["document_name"]) ? trim($input["document_name"]) : "";
$documentType = isset($input["document_type"]) ? trim($input["document_type"]) : "";
$status = isset($input["status"]) ? trim($input["status"]) : "";
$filePath = isset($input["file_path"]) ? trim($input["file_path"]) : "";

if ($id <= 0) {
    echo json_encode(["success" => false, "error" => "Document ID required"]);
    mysqli_close($conn);
    exit;
}

$updates = [];
$params = [];
$types = "";

if ($clientId > 0) {
    $updates[] = "client_id = ?";
    $params[] = $clientId;
    $types .= "i";
}
if (!empty($documentName)) {
    $updates[] = "document_name = ?";
    $params[] = $documentName;
    $types .= "s";
}
if (!empty($documentType)) {
    $updates[] = "document_type = ?";
    $params[] = $documentType;
    $types .= "s";
}
if (!empty($status)) {
    $updates[] = "status = ?";
    $params[] = $status;
    $types .= "s";
}
if (!empty($filePath)) {
    $updates[] = "file_path = ?";
    $params[] = $filePath;
    $types .= "s";
}

if (empty($updates)) {
    echo json_encode(["success" => false, "error" => "No fields to update"]);
    mysqli_close($conn);
    exit;
}

$updates[] = "updated_at = NOW()";
$params[] = $id;
$types .= "i";

$query = "UPDATE client_documents SET " . implode(", ", $updates) . " WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    echo json_encode(["success" => false, "error" => "Prepare failed: " . mysqli_error($conn)]);
    mysqli_close($conn);
    exit;
}

mysqli_stmt_bind_param($stmt, $types, ...$params);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "success" => true,
        "message" => "Document updated successfully",
        "affected_rows" => mysqli_stmt_affected_rows($stmt)
    ]);
} else {
    echo json_encode(["success" => false, "error" => mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
exit;
?>';

if (file_put_contents('update_client_document.php', $content)) {
    echo "File created! Size: " . filesize('update_client_document.php');
} else {
    echo "Failed to create file";
}
?>