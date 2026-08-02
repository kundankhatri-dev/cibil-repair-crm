<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$document_id = $data['document_id'] ?? 0;
$client_id = $data['client_id'] ?? 0;
$signer_name = $data['signer_name'] ?? '';
$signer_email = $data['signer_email'] ?? '';
$signature_hash = hash('sha256', uniqid() . $document_id . $client_id);
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$certificate_number = 'CERT-' . strtoupper(uniqid());

if (!$document_id || !$client_id || !$signer_name) {
    echo json_encode(['success' => false, 'error' => 'Document ID, Client ID, and Signer name required']);
    exit;
}

$query = "INSERT INTO dm_esignatures (document_id, client_id, signer_name, signer_email, signature_hash, ip_address, certificate_number) 
          VALUES ($document_id, $client_id, '$signer_name', '$signer_email', '$signature_hash', '$ip_address', '$certificate_number')";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Signature request sent']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>