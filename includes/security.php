<?php
/**
 * Security Functions
 * CSRF protection, XSS prevention, input sanitization
 */

require_once __DIR__ . '/config.php';

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

/**
 * Sanitize output for HTML display (XSS prevention)
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
        return $data;
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hash password with bcrypt
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate secure random token
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Check if user has admin role
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        die('Access denied. Admin privileges required.');
    }
}

/**
 * Rate limiting check
 */
function checkRateLimit($identifier, $maxRequests = null, $period = null) {
    if (!RATE_LIMIT_ENABLED) {
        return true;
    }
    
    $maxRequests = $maxRequests ?? RATE_LIMIT_REQUESTS;
    $period = $period ?? RATE_LIMIT_PERIOD;
    
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 1,
            'start_time' => time()
        ];
        return true;
    }
    
    $elapsed = time() - $_SESSION[$key]['start_time'];
    
    if ($elapsed > $period) {
        $_SESSION[$key] = [
            'count' => 1,
            'start_time' => time()
        ];
        return true;
    }
    
    if ($_SESSION[$key]['count'] >= $maxRequests) {
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowedTypes = null) {
    $allowedTypes = $allowedTypes ?? explode(',', ALLOWED_IMAGE_TYPES);
    
    // Check if file was uploaded
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file upload'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error: ' . $file['error']];
    }
    
    // Check file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum allowed'];
    }
    
    // Check file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    // Check MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    $allowedMimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    ];
    
    if (!isset($allowedMimeTypes[$ext]) || $allowedMimeTypes[$ext] !== $mimeType) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    return ['success' => true];
}

/**
 * Create secure filename
 */
function createSecureFilename($originalFilename) {
    $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
    return uniqid() . '_' . time() . '.' . $ext;
}

/**
 * Set security headers
 */
function setSecurityHeaders() {
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.tiny.cloud; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; img-src 'self' data: https:; font-src 'self' data:;");
    
    // Other security headers
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // HSTS (only if using HTTPS)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
}

/**
 * Clean old sessions
 */
function cleanOldSessions() {
    $db = getDBConnection();
    $stmt = $db->prepare("DELETE FROM sessions WHERE expires_at < NOW()");
    $stmt->execute();
}

/**
 * Log security event
 */
function logSecurityEvent($event, $details = '') {
    $logFile = __DIR__ . '/../logs/security.log';
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $logMessage = "[$timestamp] [$ip] [$event] $details - User Agent: $userAgent\n";
    
    error_log($logMessage, 3, $logFile);
}
