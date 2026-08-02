<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$response = [];

try {
    // Total documents
    $query = "SELECT COUNT(*) as total FROM dm_documents WHERE status != 'archived'";
    $result = mysqli_query($conn, $query);
    $total_documents = mysqli_fetch_assoc($result)['total'];
    
    // Pending documents
    $query = "SELECT COUNT(*) as total FROM dm_documents WHERE status = 'pending'";
    $result = mysqli_query($conn, $query);
    $pending_documents = mysqli_fetch_assoc($result)['total'];
    
    // Expired documents
    $query = "SELECT COUNT(*) as total FROM dm_documents WHERE expiry_date < CURDATE() AND expiry_date IS NOT NULL";
    $result = mysqli_query($conn, $query);
    $expired_documents = mysqli_fetch_assoc($result)['total'];
    
    // Signatures today
    $query = "SELECT COUNT(*) as total FROM dm_esignatures WHERE DATE(signed_at) = CURDATE()";
    $result = mysqli_query($conn, $query);
    $signatures_today = mysqli_fetch_assoc($result)['total'];
    
    // Document type distribution
    $query = "SELECT 
                CASE 
                    WHEN mime_type LIKE '%pdf%' THEN 'PDF'
                    WHEN mime_type LIKE '%word%' THEN 'Word'
                    WHEN mime_type LIKE '%excel%' THEN 'Excel'
                    WHEN mime_type LIKE '%image%' THEN 'Image'
                    ELSE 'Other'
                END as doc_type,
                COUNT(*) as count
              FROM dm_documents 
              GROUP BY doc_type";
    $result = mysqli_query($conn, $query);
    $type_distribution = ['labels' => [], 'values' => []];
    while ($row = mysqli_fetch_assoc($result)) {
        $type_distribution['labels'][] = $row['doc_type'];
        $type_distribution['values'][] = (int)$row['count'];
    }
    
    // Recent documents
    $query = "SELECT d.*, f.folder_name 
              FROM dm_documents d
              LEFT JOIN dm_folders f ON d.folder_id = f.id
              ORDER BY d.uploaded_at DESC LIMIT 10";
    $result = mysqli_query($conn, $query);
    $recent_documents = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_documents[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'total_documents' => (int)$total_documents,
        'pending_documents' => (int)$pending_documents,
        'expired_documents' => (int)$expired_documents,
        'signatures_today' => (int)$signatures_today,
        'type_distribution' => $type_distribution,
        'recent_documents' => $recent_documents
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>