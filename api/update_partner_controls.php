<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['id'])) {
    die(json_encode(['success' => false, 'error' => 'Invalid request']));
}

require_once '../db.php';

$stmt = $pdo->prepare("
    UPDATE partners SET 
        name = :name,
        phone = :phone,
        location = :location,
        status = :status,
        tier = :tier,
        commission_rate = :commission_rate,
        allow_payouts = :allow_payouts,
        allow_referrals = :allow_referrals
    WHERE id = :id
");
$success = $stmt->execute([
    ':id' => $input['id'],
    ':name' => $input['name'],
    ':phone' => $input['phone'],
    ':location' => $input['location'],
    ':status' => $input['status'],
    ':tier' => $input['tier'],
    ':commission_rate' => $input['commission_rate'],
    ':allow_payouts' => $input['allow_payouts'],
    ':allow_referrals' => $input['allow_referrals']
]);

echo json_encode(['success' => $success]);