<?php
// ============================================================
// SHARED CONFIGURATION — CIBIL Repair CRM
// Include this file (require_once) at the top of every page that
// needs the database. Centralizes credentials so they only ever
// live in one place.
//
// To override on a different environment (staging, local dev),
// set these as real environment variables instead of editing this
// file — getenv() takes priority when present.
// ============================================================

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'u929623538_cibil');
define('DB_USER', getenv('DB_USER') ?: 'u929623538_cibilrepair');
define('DB_PASS', getenv('DB_PASS') ?: 'Kundanlaxmi@1995');

/**
 * Shared PDO connection (login.php, partner-dashboard.php, client-dashboard.php).
 * Reuses one connection per request instead of opening a new one every include.
 */
function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pdo;
}

/**
 * Shared mysqli connection (admin-dashboard.php only — it was built on mysqli).
 * Left as mysqli rather than forced onto PDO to avoid rewriting every
 * query in that file; both connections point at the same credentials
 * above so there's only ever one place to update them.
 */
function getMysqli(): mysqli|false {
    static $conn = null;
    if ($conn === null) {
        $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn) {
            error_log("DB connection failed: " . mysqli_connect_error());
        }
    }
    return $conn;
}

/**
 * Shared HTML-escape helper — was previously redefined identically in
 * every dashboard file.
 */
if (!function_exists('h')) {
    function h(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
