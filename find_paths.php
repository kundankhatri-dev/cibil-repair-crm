<?php
// ============================================================
// PATH DISCOVERY - Find where all files are located
// ============================================================

header('Content-Type: text/html; charset=utf-8');

$base_dir = __DIR__;
$results = [];

// Function to search for dashboard files recursively
function searchDashboards($dir, &$results, $depth = 0) {
    if ($depth > 3) return; // Limit search depth
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        
        $path = $dir . '/' . $item;
        
        if (is_dir($path)) {
            searchDashboards($path, $results, $depth + 1);
        } elseif (strpos($item, 'dashboard') !== false && pathinfo($item, PATHINFO_EXTENSION) === 'php') {
            $results[] = [
                'file' => $item,
                'path' => $path,
                'relative' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $path)
            ];
        }
    }
}

// Search current directory
searchDashboards($base_dir, $results);

// Also check common folder names
$common_folders = ['dashboards', 'admin', 'panel', 'crm', 'backend', 'app', 'public'];
foreach ($common_folders as $folder) {
    $folder_path = $base_dir . '/' . $folder;
    if (is_dir($folder_path)) {
        searchDashboards($folder_path, $results, 0);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Path Finder</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #0a0e27; color: #fff; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #0d9e78; }
        table { width: 100%; border-collapse: collapse; background: #1a1a2e; border-radius: 12px; overflow: hidden; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #2a2a3e; }
        th { background: #0d9e78; }
        .success { color: #10b981; }
        .warning { color: #f59e0b; }
        a { color: #0d9e78; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .test-link { margin-top: 20px; padding: 20px; background: #1a1a2e; border-radius: 12px; }
        input { padding: 10px; width: 300px; border: 1px solid #2a2a3e; background: #0a0e27; color: #fff; border-radius: 8px; }
        button { padding: 10px 20px; background: #0d9e78; border: none; border-radius: 8px; color: #fff; cursor: pointer; }
        button:hover { background: #0a7d60; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Dashboard Path Discovery Tool</h1>
    
    <?php if (empty($results)): ?>
        <div class="warning" style="background: #1a1a2e; padding: 20px; border-radius: 12px;">
            <h3>⚠️ No dashboard files found in the search!</h3>
            <p>Your dashboard files might be:</p>
            <ul>
                <li>In a different directory not accessible via web root</li>
                <li>Named differently (e.g., admin-panel.php, index.php)</li>
                <li>Not uploaded yet</li>
            </ul>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>#</th><th>File Name</th><th>Full Path</th><th>Test URL</th></tr>
            </thead>
            <tbody>
                <?php foreach ($results as $index => $file): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><strong><?= $file['file'] ?></strong></td>
                    <td style="font-size: 11px; color: #6b7280;"><?= $file['path'] ?></td>
                    <td><a href="<?= $file['relative'] ?>" target="_blank">🔗 Test →</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="test-link">
            <h3>🧪 Quick Test</h3>
            <p>Enter a path to test:</p>
            <input type="text" id="testPath" placeholder="e.g., /dashboards/admin_dashboard.php">
            <button onclick="testPath()">Test</button>
            <div id="testResult" style="margin-top: 10px;"></div>
        </div>
    <?php endif; ?>
    
    <div class="test-link" style="margin-top: 20px;">
        <h3>📂 Directory Listing</h3>
        <p>Current directory contents:</p>
        <ul>
            <?php
            $items = scandir($base_dir);
            foreach ($items as $item) {
                if ($item != '.' && $item != '..') {
                    $is_dir = is_dir($base_dir . '/' . $item);
                    echo '<li>' . ($is_dir ? '📁' : '📄') . ' ' . $item . '</li>';
                }
            }
            ?>
        </ul>
    </div>
</div>

<script>
function testPath() {
    const path = document.getElementById('testPath').value;
    const resultDiv = document.getElementById('testResult');
    
    fetch(path, { method: 'HEAD' })
        .then(response => {
            if (response.ok) {
                resultDiv.innerHTML = `<span style="color: #10b981;">✅ Working! Status: ${response.status}</span><br>
                                       <a href="${path}" target="_blank">Open Dashboard →</a>`;
            } else {
                resultDiv.innerHTML = `<span style="color: #ef4444;">❌ Not Found (Status: ${response.status})</span>`;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<span style="color: #ef4444;">❌ Error: ${error}</span>`;
        });
}

// Also test common paths automatically
const commonPaths = [
    '/admin_dashboard.php',
    '/dashboards/admin_dashboard.php',
    '/admin/admin_dashboard.php',
    '/panel/admin_dashboard.php',
    '/crm/admin_dashboard.php',
    '/backend/admin_dashboard.php',
    '/app/admin_dashboard.php',
    '/public/admin_dashboard.php'
];

console.log('Testing common paths...');
commonPaths.forEach(path => {
    fetch(path, { method: 'HEAD' })
        .then(r => console.log(`${path}: ${r.status === 200 ? '✅' : '❌'} (${r.status})`))
        .catch(e => console.log(`${path}: ❌ Error`));
});
</script>
</body>
</html>