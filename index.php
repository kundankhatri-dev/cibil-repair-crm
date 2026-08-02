<?php
$request = $_SERVER['REQUEST_URI'];
$path = trim($request, '/');

if (empty($path)) {
    include 'index.html';
} elseif (file_exists($path . '.html')) {
    include $path . '.html';
} elseif (file_exists($path . '.php')) {
    include $path . '.php';
} else {
    http_response_code(404);
    echo '404 - Page not found';
}
?>