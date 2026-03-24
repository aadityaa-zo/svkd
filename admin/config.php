<?php
session_start();

// Admin credentials
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123'); // In a real app, use password_hash()

// Upload directory (normalized for Windows/Linux)
define('UPLOAD_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);

// Automatically create uploads directory if it doesn't exist
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Function to check if logged in
function check_login() {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: login.php');
        exit;
    }
}
?>
