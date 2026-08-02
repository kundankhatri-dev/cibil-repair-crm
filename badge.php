<?php
// badge.php - Shows deployment status as badge
$status = file_get_contents('/home/u929623538/domains/cibilrepair.in/public_html/storage/logs/auto-deploy.log');
$last_deploy = date('Y-m-d H:i:s', filemtime('/home/u929623538/domains/cibilrepair.in/public_html/storage/logs/auto-deploy.log'));

header('Content-Type: image/svg+xml');
?>
<svg xmlns="http://www.w3.org/2000/svg" width="120" height="20">
    <rect width="120" height="20" fill="#555"/>
    <rect width="70" height="20" fill="#4c1"/>
    <text x="10" y="15" fill="#fff" font-size="11" font-family="Arial">deploy</text>
    <text x="80" y="15" fill="#fff" font-size="11" font-family="Arial"><?= date('H:i') ?></text>
</svg>
