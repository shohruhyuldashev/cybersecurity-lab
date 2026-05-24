<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'webapp');
define('DB_PASS', 'WebApp#2024!');
define('DB_NAME', 'adminpanel');
define('APP_NAME', 'AdminPanel');
define('APP_VERSION', '3.2.1');

function get_db() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['error' => 'DB connection failed: ' . $conn->connect_error]));
    }
    return $conn;
}

function sanitize($v) { return htmlspecialchars(strip_tags($v)); }

if (session_status() === PHP_SESSION_NONE) session_start();
function is_logged_in() { return isset($_SESSION['admin_id']); }
function require_login() { if (!is_logged_in()) { header('Location: /index.php'); exit(); } }
?>
