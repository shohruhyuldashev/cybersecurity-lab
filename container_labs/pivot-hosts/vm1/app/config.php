<?php
// DocuShare - Document Management System
// Configuration file

define('DB_PATH', '/var/db/users.db');
define('UPLOAD_DIR', '/var/www/html/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('APP_NAME', 'DocuShare');
define('APP_VERSION', '2.4.1');

function get_db() {
    $db = new SQLite3(DB_PATH);
    $db->busyTimeout(5000);
    return $db;
}

function hash_password($pass) {
    return hash('sha256', $pass);
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function require_login() {
    if (!is_logged_in()) {
        redirect('/login.php');
    }
}
?>
