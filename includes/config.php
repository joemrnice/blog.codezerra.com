<?php
/**
 * Configuration File
 * Main configuration and database connection
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        if (!array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
    return true;
}

// Load .env file
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    loadEnv($envPath);
}

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'codezerra_blog');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// Site Configuration
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost');
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'admin@example.com');

// Security Configuration
define('SESSION_SECRET', getenv('SESSION_SECRET') ?: 'change_this_secret_key');
define('CSRF_SECRET', getenv('CSRF_SECRET') ?: 'change_this_csrf_secret');

// File Upload Configuration
define('MAX_UPLOAD_SIZE', getenv('MAX_UPLOAD_SIZE') ?: 5242880); // 5MB default
define('ALLOWED_IMAGE_TYPES', getenv('ALLOWED_IMAGE_TYPES') ?: 'jpg,jpeg,png,gif,webp');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Rate Limiting
define('RATE_LIMIT_ENABLED', getenv('RATE_LIMIT_ENABLED') === 'true');
define('RATE_LIMIT_REQUESTS', getenv('RATE_LIMIT_REQUESTS') ?: 100);
define('RATE_LIMIT_PERIOD', getenv('RATE_LIMIT_PERIOD') ?: 3600);

// Error Configuration
$displayErrors = getenv('DISPLAY_ERRORS');
if ($displayErrors === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    $errorLog = getenv('ERROR_LOG_PATH');
    if ($errorLog) {
        ini_set('error_log', $errorLog);
    }
}

// Chatbot Configuration
define('CHATBOT_ENABLED', getenv('CHATBOT_ENABLED') !== 'false');
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');

// Path Configuration
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', __DIR__);

// Database Connection
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
    }
    
    return $conn;
}

// Timezone Configuration
date_default_timezone_set('UTC');
