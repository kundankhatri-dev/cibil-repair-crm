<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT wi.*, d.document_name, w.workflow_name 
          FROM dm_workflow_instances wi
          JOIN dm_documents d ON wi.document_id = d.id
          JOIN dm_workflows w ON wi.workflow_id = w.id
          WHERE wi.status IN ('pending', 'in_progress')
          ORDER BY wi.started_at DESC";
$result = mysqli_query($conn, $query);

$workflows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $workflows[] = $row;
}

echo json_encode(['success' => true, 'workflows' => $workflows, 'total' => count($workflows)]);
?>