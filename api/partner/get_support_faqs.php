<?php
// api/partner/get_faqs.php
// Partner Get FAQs API - Retrieve frequently asked questions

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: max-age=3600'); // Cache for 1 hour

// Check database connection
if (!$conn) {
    // Fallback to static FAQs if database connection fails
    serveStaticFAQs();
    exit;
}

// ========== ENSURE FAQS TABLE EXISTS ==========
$faqsTable = 'partner_faqs';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$faqsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    // Create FAQs table
    $createTable = "CREATE TABLE IF NOT EXISTS $faqsTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question VARCHAR(500) NOT NULL,
        answer TEXT NOT NULL,
        category VARCHAR(50) DEFAULT 'general',
        display_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_is_active (is_active),
        INDEX idx_display_order (display_order)
    )";
    mysqli_query($conn, $createTable);
    
    // Insert default FAQs
    $defaultFaqs = [
        ['How do I add a new lead?', 'You can add a new lead by clicking on the "Add Lead" button in the Leads section. Fill in the customer details and submit.', 'leads', 1],
        ['When do I get my commission?', 'Commission is credited when a lead gets converted. Payouts are processed on the 1st and 15th of every month.', 'commission', 2],
        ['How is commission calculated?', 'Commission is 10% of the service amount. For example, if a customer pays ₹15,000 for Written Off Clearance, you earn ₹1,500.', 'commission', 3],
        ['How do I request a payout?', 'Go to the Payouts section and click "Request Payout". Enter the amount (minimum ₹500) and submit. You need to add bank details first.', 'payout', 4],
        ['How long does payout take?', 'Payouts are processed within 3-5 business days after request approval.', 'payout', 5],
        ['What is the minimum payout amount?', 'The minimum payout amount is ₹500.', 'payout', 6],
        ['How do I track my leads?', 'You can view all your leads in the "My Leads" section. Use filters to sort by status or search by customer name.', 'leads', 7],
        ['What services can I offer?', 'You can offer Written Off Clearance, Settled Clearance, Suit Filed Clearance, Credit Report Analysis, Profile Correction, and Wrong Entry Clearance.', 'services', 8],
        ['How do I update my profile?', 'Go to the Profile section to update your name, phone number, and company details.', 'profile', 9],
        ['How do I add bank details for payout?', 'Go to the Bank Details section and fill in your bank account information. This is required for receiving payouts.', 'profile', 10]
    ];
    
    $insertStmt = mysqli_prepare($conn, "INSERT INTO $faqsTable (question, answer, category, display_order) VALUES (?, ?, ?, ?)");
    foreach ($defaultFaqs as $faq) {
        mysqli_stmt_bind_param($insertStmt, "sssi", $faq[0], $faq[1], $faq[2], $faq[3]);
        mysqli_stmt_execute($insertStmt);
    }
    mysqli_stmt_close($insertStmt);
}

// ========== GET FILTER PARAMETERS ==========
$category = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Validate limit
if ($limit < 1 || $limit > 100) {
    $limit = 50;
}

// ========== BUILD QUERY ==========
$query = "SELECT 
            id,
            question,
            answer,
            category,
            display_order,
            DATE_FORMAT(created_at, '%d-%m-%Y') as created_date
          FROM $faqsTable 
          WHERE is_active = 1";

$params = [];
$types = "";

if ($category !== 'all' && !empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (question LIKE ? OR answer LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$query .= " ORDER BY display_order ASC, id ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    serveStaticFAQs();
    exit;
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$faqs = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ========== GET CATEGORY COUNTS ==========
$categoryQuery = "SELECT 
                    category,
                    COUNT(*) as count
                  FROM $faqsTable 
                  WHERE is_active = 1
                  GROUP BY category
                  ORDER BY count DESC";
$categoryResult = mysqli_query($conn, $categoryQuery);
$categoryCounts = [];
while ($row = mysqli_fetch_assoc($categoryResult)) {
    $categoryCounts[] = $row;
}

// ========== GET TOTAL COUNT ==========
$countQuery = "SELECT COUNT(*) as total FROM $faqsTable WHERE is_active = 1";
$countParams = [];
$countTypes = "";

if ($category !== 'all' && !empty($category)) {
    $countQuery .= " AND category = ?";
    $countParams[] = $category;
    $countTypes .= "s";
}

if (!empty($search)) {
    $countQuery .= " AND (question LIKE ? OR answer LIKE ?)";
    $countParams[] = $search_param;
    $countParams[] = $search_param;
    $countTypes .= "ss";
}

$countStmt = mysqli_prepare($conn, $countQuery);
if ($countStmt) {
    if (!empty($countParams)) {
        mysqli_stmt_bind_param($countStmt, $countTypes, ...$countParams);
    }
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $totalCount = mysqli_fetch_assoc($countResult)['total'] ?? 0;
    mysqli_stmt_close($countStmt);
} else {
    $totalCount = count($faqs);
}

// ========== GET POPULAR FAQS (Most viewed - if tracking table exists) ==========
$popularFaqs = [];
$popularTable = 'faq_views';
$checkPopularTable = mysqli_query($conn, "SHOW TABLES LIKE '$popularTable'");
if (mysqli_num_rows($checkPopularTable) > 0) {
    $popularQuery = "SELECT 
                        f.id, f.question, f.answer, f.category,
                        COUNT(v.id) as view_count
                      FROM $faqsTable f
                      LEFT JOIN $popularTable v ON f.id = v.faq_id
                      WHERE f.is_active = 1
                      GROUP BY f.id
                      ORDER BY view_count DESC, f.display_order ASC
                      LIMIT 5";
    $popularResult = mysqli_query($conn, $popularQuery);
    $popularFaqs = mysqli_fetch_all($popularResult, MYSQLI_ASSOC);
}

// Also create view tracking table
if (mysqli_num_rows($checkPopularTable) == 0) {
    $createViewsTable = "CREATE TABLE IF NOT EXISTS faq_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        faq_id INT NOT NULL,
        partner_id INT NULL,
        ip_address VARCHAR(45),
        viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_faq_id (faq_id),
        INDEX idx_partner_id (partner_id)
    )";
    mysqli_query($conn, $createViewsTable);
}

// Track view for this API call (if faq_id provided)
$faq_id = isset($_GET['faq_id']) ? (int)$_GET['faq_id'] : 0;
if ($faq_id > 0) {
    $partner_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $trackStmt = mysqli_prepare($conn, "INSERT INTO faq_views (faq_id, partner_id, ip_address, viewed_at) VALUES (?, ?, ?, NOW())");
    if ($trackStmt) {
        mysqli_stmt_bind_param($trackStmt, "iis", $faq_id, $partner_id, $ip_address);
        mysqli_stmt_execute($trackStmt);
        mysqli_stmt_close($trackStmt);
    }
    
    // Get single FAQ
    $singleQuery = "SELECT id, question, answer, category FROM $faqsTable WHERE id = ? AND is_active = 1";
    $singleStmt = mysqli_prepare($conn, $singleQuery);
    mysqli_stmt_bind_param($singleStmt, "i", $faq_id);
    mysqli_stmt_execute($singleStmt);
    $singleResult = mysqli_stmt_get_result($singleStmt);
    $singleFaq = mysqli_fetch_assoc($singleResult);
    mysqli_stmt_close($singleStmt);
    
    if ($singleFaq) {
        echo json_encode([
            'success' => true,
            'faq' => $singleFaq,
            'related_faqs' => getRelatedFaqs($conn, $faqsTable, $singleFaq['category'], $faq_id)
        ]);
        exit;
    }
}

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'faqs' => $faqs,
    'total' => count($faqs),
    'total_all' => (int)$totalCount,
    'categories' => $categoryCounts,
    'popular_faqs' => $popularFaqs,
    'filters' => [
        'category' => $category,
        'search' => $search,
        'limit' => $limit,
        'offset' => $offset
    ],
    'pagination' => [
        'current_page' => floor($offset / $limit) + 1,
        'per_page' => $limit,
        'total_pages' => ceil($totalCount / $limit),
        'total_records' => (int)$totalCount
    ],
    'last_updated' => date('Y-m-d H:i:s')
]);

// ========== HELPER FUNCTIONS ==========
function getRelatedFaqs($conn, $table, $category, $current_id) {
    $query = "SELECT id, question FROM $table WHERE category = ? AND id != ? AND is_active = 1 LIMIT 3";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $category, $current_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $related = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $related;
}

function serveStaticFAQs() {
    $staticFaqs = [
        ['id' => 1, 'question' => 'How do I add a new lead?', 'answer' => 'Click on "Add Lead" button in Leads section.', 'category' => 'leads'],
        ['id' => 2, 'question' => 'When do I get my commission?', 'answer' => 'Commission is credited on lead conversion. Payouts on 1st and 15th.', 'category' => 'commission'],
        ['id' => 3, 'question' => 'What is the minimum payout?', 'answer' => 'Minimum payout amount is ₹500.', 'category' => 'payout']
    ];
    
    echo json_encode([
        'success' => true,
        'faqs' => $staticFaqs,
        'total' => count($staticFaqs),
        'categories' => [['category' => 'leads', 'count' => 1], ['category' => 'commission', 'count' => 1], ['category' => 'payout', 'count' => 1]],
        'is_fallback' => true
    ]);
    exit;
}

mysqli_close($conn);
?>