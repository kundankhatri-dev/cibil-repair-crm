<?php
header('Content-Type: application/json');

// Database configuration
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$minRating = isset($_GET['min_rating']) ? (int)$_GET['min_rating'] : 1;

// Check if reviews table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'reviews'");
    $tableExists = $stmt->rowCount() > 0;
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database query failed',
        'debug' => $e->getMessage()
    ]);
    exit;
}

if (!$tableExists) {
    // Create the reviews table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255),
            review_text TEXT NOT NULL,
            rating INT DEFAULT 5,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Insert sample reviews
        $sampleReviews = [
            ['Rajesh Kumar', 'rajesh@example.com', 'Excellent service! My CIBIL score improved significantly. The team was very professional and guided me through the entire process.', 5],
            ['Priya Sharma', 'priya@example.com', 'Very professional team. Helped me clear my settled accounts and improved my credit score by 80 points.', 5],
            ['Amit Singh', 'amit@example.com', 'Best credit repair service in India. Highly recommended! They resolved all my credit issues within 45 days.', 5],
            ['Sneha Patel', 'sneha@example.com', 'Great work! They resolved all my credit issues and helped me understand my credit report better.', 4],
            ['Vikram Reddy', 'vikram@example.com', 'Professional and transparent process. Thank you for helping me get my loan approved!', 5]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO reviews (name, email, review_text, rating) VALUES (?, ?, ?, ?)");
        foreach ($sampleReviews as $review) {
            $stmt->execute($review);
        }
        
        // Get the inserted reviews - FIXED: use bindParam instead of direct values
        $stmt = $pdo->prepare("SELECT * FROM reviews WHERE rating >= ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bindParam(1, $minRating, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $reviews = $stmt->fetchAll();
        
        // Format dates and add stars
        foreach ($reviews as &$review) {
            $review['formatted_date'] = date('d M Y', strtotime($review['created_at']));
            $review['stars'] = str_repeat('⭐', $review['rating']);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $reviews,
            'total' => count($reviews),
            'stats' => ['average_rating' => 4.8],
            'message' => 'Table created with sample data'
        ]);
        exit;
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Table creation failed',
            'debug' => $e->getMessage()
        ]);
        exit;
    }
}

// Get reviews from database - FIXED: use bindParam
try {
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE rating >= ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bindParam(1, $minRating, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $reviews = $stmt->fetchAll();
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch reviews',
        'debug' => $e->getMessage()
    ]);
    exit;
}

// Get stats
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total, AVG(rating) as avg_rating FROM reviews");
    $stats = $stmt->fetch();
} catch(PDOException $e) {
    $stats = ['total' => count($reviews), 'avg_rating' => 0];
}

// Format dates and add stars
foreach ($reviews as &$review) {
    $review['formatted_date'] = date('d M Y', strtotime($review['created_at']));
    $review['stars'] = str_repeat('⭐', $review['rating']);
}

// Return success response
echo json_encode([
    'success' => true,
    'data' => $reviews,
    'total' => (int)$stats['total'],
    'stats' => [
        'average_rating' => round($stats['avg_rating'], 1)
    ]
]);
exit;
?>