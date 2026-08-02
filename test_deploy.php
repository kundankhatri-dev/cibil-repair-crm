<?php
// test_deploy.php - Test deployment setup

echo "<h1>🔧 Deployment Test</h1>";

// Test 1: PHP version
echo "<h3>1️⃣ PHP Version</h3>";
echo "<pre>" . phpversion() . "</pre>";

// Test 2: Database connection
echo "<h3>2️⃣ Database Connection</h3>";
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=u929623538_cibil",
        "u929623538_cibilrepair",
        "Kundanlaxmi@1995"
    );
    echo "✅ <span style='color:green'>Connected successfully!</span><br>";
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "📊 Users count: <strong>$count</strong><br>";
} catch (Exception $e) {
    echo "❌ <span style='color:red'>Connection failed: " . $e->getMessage() . "</span><br>";
}

// Test 3: File permissions
echo "<h3>3️⃣ File Permissions</h3>";
$dirs = ['storage/', 'storage/logs/', 'storage/cache/', 'public/uploads/'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir) ? "✅ Writable" : "❌ Not writable";
        echo "📁 $dir - Permissions: $perms - $writable<br>";
    } else {
        echo "❌ $dir - <span style='color:red'>Does not exist</span><br>";
    }
}

// Test 4: Console
echo "<h3>4️⃣ Console Test</h3>";
echo "<pre>";
passthru("php bin/console health 2>&1");
echo "</pre>";

echo "<h3>✅ Deployment setup is ready!</h3>";
echo "<p>Next steps:</p>";
echo "<ul>";
echo "<li>Upload your code to GitHub</li>";
echo "<li>Run: <code>php bin/console deploy</code> via SSH</li>";
echo "<li>Or use GitHub Actions for automated deployment</li>";
echo "</ul>";
?>