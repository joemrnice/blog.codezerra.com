<?php
/**
 * Admin Logout
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Delete session from database
if (isset($_SESSION['session_token'])) {
    deleteSession($_SESSION['session_token']);
}

// Log the logout
if (isset($_SESSION['username'])) {
    logSecurityEvent('User logout', 'Username: ' . $_SESSION['username']);
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login
redirect('/admin/login.php');
