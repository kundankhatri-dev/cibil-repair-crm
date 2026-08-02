<?php
// ============================================================
// DYNAMIC SITEMAP GENERATOR - CLEAN URLs
// Generates XML sitemap with NO .php or .html extensions
// Auto-detects pages from database + file system
// ============================================================

// Headers
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex, follow');
header('Cache-Control: no-cache, must-revalidate');

// Your website details
$site_url = 'https://cibilrepair.in';
$today = date('Y-m-d');

// Include database connection (if available)
$conn = null;
try {
    require_once 'config/database.php';
    $conn = Database::getInstance()->getConnection();
} catch (Exception $e) {
    // Database not available - continue with static pages only
    error_log("Sitemap: Database not available - " . $e->getMessage());
}

// Start XML output
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

// ============================================================
// 1. MAIN PAGES (Static - Clean URLs)
// ============================================================
$pages = [];

$static_pages = [
    ['url' => '/', 'priority' => '1.0', 'freq' => 'daily', 'file' => 'index.html'],
    ['url' => '/about-us', 'priority' => '0.9', 'freq' => 'monthly', 'file' => 'about-us.html'],
    ['url' => '/services', 'priority' => '0.9', 'freq' => 'weekly', 'file' => 'services.html'],
    ['url' => '/success-stories', 'priority' => '0.8', 'freq' => 'weekly', 'file' => 'success-stories.html'],
    ['url' => '/contact', 'priority' => '0.9', 'freq' => 'monthly', 'file' => 'contact.html'],
    ['url' => '/blog', 'priority' => '0.8', 'freq' => 'daily', 'file' => 'blog.php'],
    ['url' => '/partners', 'priority' => '0.9', 'freq' => 'weekly', 'file' => 'partners'],
    ['url' => '/careers', 'priority' => '0.7', 'freq' => 'weekly', 'file' => 'careers.html'],
];

// ============================================================
// 2. SERVICE PAGES (6 Total - Clean URLs - NO .php)
// ============================================================
$service_pages = [
    ['url' => '/service/written-off', 'priority' => '0.9', 'freq' => 'weekly', 'file' => 'service/written-off.php'],
    ['url' => '/service/settled', 'priority' => '0.9', 'freq' => 'weekly', 'file' => 'service/settled.php'],
    ['url' => '/service/suit-filled', 'priority' => '0.9', 'freq' => 'weekly', 'file' => 'service/suit-filled.php'],
    ['url' => '/service/analysis', 'priority' => '0.85', 'freq' => 'weekly', 'file' => 'service/analysis.php'],
    ['url' => '/service/profile', 'priority' => '0.85', 'freq' => 'weekly', 'file' => 'service/profile.php'],
    ['url' => '/service/wrong-entry', 'priority' => '0.85', 'freq' => 'weekly', 'file' => 'service/wrong-entry.php'],
];

// ============================================================
// 3. PRICING / PLAN PAGES (3 Total - Starter, Professional, Enterprises)
// ============================================================
$pricing_pages = [
    ['url' => '/pricing/starter', 'priority' => '0.8', 'freq' => 'monthly', 'file' => 'pricing/starter.php'],
    ['url' => '/pricing/professional', 'priority' => '0.8', 'freq' => 'monthly', 'file' => 'pricing/professional.php'],
    ['url' => '/pricing/enterprises', 'priority' => '0.8', 'freq' => 'monthly', 'file' => 'pricing/enterprises.php'],
];

// ============================================================
// 4. LEGAL PAGES
// ============================================================
$legal_pages = [
    ['url' => '/privacy-policy', 'priority' => '0.4', 'freq' => 'yearly', 'file' => 'privacy-policy.html'],
    ['url' => '/terms-conditions', 'priority' => '0.4', 'freq' => 'yearly', 'file' => 'terms-conditions.html'],
    ['url' => '/refund-cancellation', 'priority' => '0.4', 'freq' => 'yearly', 'file' => 'refund-cancellation.html'],
    ['url' => '/disclaimer', 'priority' => '0.3', 'freq' => 'yearly', 'file' => 'disclaimer.html'],
    ['url' => '/complaints', 'priority' => '0.4', 'freq' => 'monthly', 'file' => 'complaints.html'],
    ['url' => '/rbi-compliance', 'priority' => '0.4', 'freq' => 'yearly', 'file' => 'rbi-compliance.html'],
];

// ============================================================
// 5. MERGE STATIC PAGES
// ============================================================
$all_pages = array_merge($static_pages, $service_pages, $pricing_pages, $legal_pages);

// ============================================================
// 6. SUCCESS STORIES (From Database - Clean URLs)
// ============================================================
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT id, name, created_at, updated_at FROM success_stories WHERE status = 'approved' ORDER BY created_at DESC LIMIT 200");
        $stmt->execute();
        $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($stories as $story) {
            $lastmod = !empty($story['updated_at']) ? date('Y-m-d', strtotime($story['updated_at'])) : date('Y-m-d', strtotime($story['created_at']));
            $all_pages[] = [
                'url' => "/success-story/{$story['id']}",
                'priority' => '0.7',
                'freq' => 'monthly',
                'lastmod' => $lastmod,
                'file' => null
            ];
        }
    } catch (Exception $e) {
        error_log("Sitemap: Could not fetch success stories - " . $e->getMessage());
    }

    // ============================================================
    // 7. BLOG POSTS (From Database - Clean URLs)
    // ============================================================
    try {
        $stmt = $conn->prepare("SHOW TABLES LIKE 'blog_posts'");
        $stmt->execute();
        $blogTableExists = $stmt->rowCount() > 0;
        
        if ($blogTableExists) {
            $stmt = $conn->prepare("SELECT id, slug, created_at, updated_at FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 200");
            $stmt->execute();
            $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($blogs as $blog) {
                $slug = !empty($blog['slug']) ? $blog['slug'] : $blog['id'];
                $lastmod = !empty($blog['updated_at']) ? date('Y-m-d', strtotime($blog['updated_at'])) : date('Y-m-d', strtotime($blog['created_at']));
                $all_pages[] = [
                    'url' => "/blog/{$slug}",
                    'priority' => '0.7',
                    'freq' => 'weekly',
                    'lastmod' => $lastmod,
                    'file' => null
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Sitemap: Could not fetch blog posts - " . $e->getMessage());
    }
}

// ============================================================
// 8. CITY PAGES (For Local SEO)
// ============================================================
// City pages removed - create pages first before adding to sitemap

// ============================================================
// 9. ADD SPECIAL PAGES
// ============================================================
$special_pages = [
    ['url' => '/sitemap.xml', 'priority' => '0.5', 'freq' => 'monthly', 'file' => 'sitemap.php'],
    ['url' => '/robots.txt', 'priority' => '0.3', 'freq' => 'monthly', 'file' => 'robots.txt'],
];

$all_pages = array_merge($all_pages, $special_pages);

// ============================================================
// 10. GENERATE XML OUTPUT (Clean URLs)
// ============================================================
foreach ($all_pages as $page) {
    // Skip admin/API pages
    if (strpos($page['url'], '/admin') !== false || 
        strpos($page['url'], '/api') !== false ||
        strpos($page['url'], '/config') !== false) {
        continue;
    }
    
    // Get last modified date
    $lastmod = $today;
    if (isset($page['lastmod'])) {
        $lastmod = $page['lastmod'];
    } elseif (isset($page['file']) && $page['file'] && file_exists($page['file'])) {
        $lastmod = date('Y-m-d', filemtime($page['file']));
    }
    
    // Output URL
    echo '    <url>' . "\n";
    echo '        <loc>' . $site_url . $page['url'] . '</loc>' . "\n";
    echo '        <lastmod>' . $lastmod . '</lastmod>' . "\n";
    echo '        <changefreq>' . $page['freq'] . '</changefreq>' . "\n";
    echo '        <priority>' . $page['priority'] . '</priority>' . "\n";
    echo '    </url>' . "\n";
}

echo '</urlset>';
?>