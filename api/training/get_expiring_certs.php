<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT ec.*, u.name as user_name, c.certification_name,
          DATEDIFF(ec.expiry_date, CURDATE()) as days_left
          FROM training_employee_certs ec
          JOIN users u ON ec.user_id = u.id
          JOIN training_certifications c ON ec.certification_id = c.id
          WHERE ec.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
          ORDER BY ec.expiry_date ASC";
$result = mysqli_query($conn, $query);

$certifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $certifications[] = $row;
}

echo json_encode(['success' => true, 'certifications' => $certifications, 'total' => count($certifications)]);
?>