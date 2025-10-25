<?php
// includes/config.php

// Define constants for file paths and limits
define('BASE_PATH', dirname(__DIR__));
define('DATA_FILE', BASE_PATH . '/data/admissions.json'); // Where admission data is saved
define('UPLOAD_DIR', BASE_PATH . '/uploads/'); // Where screenshots are saved
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB limit

// Simple Autoloader (to load classes automatically)
spl_autoload_register(function ($class_name) {
    $file = BASE_PATH . '/includes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Helper Function for formatting currency (for cleaner display)
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        // Formats the currency to Pakistani Rupees (PKR)
        return number_format($amount, 0, '.', ',');
    }
}
