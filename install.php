#!/usr/bin/env php
<?php
/**
 * Installation Script for CodeZerra Blog
 * Creates initial admin user
 */

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║          CodeZerra Blog - Installation Script           ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\n";

// Check if .env file exists
if (!file_exists(__DIR__ . '/.env')) {
    echo "❌ Error: .env file not found.\n";
    echo "Please copy .env.example to .env and configure it first.\n";
    exit(1);
}

// Load configuration
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/db.php';

// Test database connection
try {
    $db = getDBConnection();
    echo "✅ Database connection successful!\n\n";
} catch (Exception $e) {
    echo "❌ Error: Could not connect to database.\n";
    echo "Please check your database credentials in .env file.\n";
    exit(1);
}

// Check if admin user already exists
$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$result = $stmt->fetch();

if ($result['count'] > 0) {
    echo "⚠️  Warning: Admin user already exists.\n";
    echo "Do you want to create another admin user? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $response = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($response) !== 'y') {
        echo "\nInstallation cancelled.\n";
        exit(0);
    }
    echo "\n";
}

// Get admin credentials
echo "Creating Admin User\n";
echo "═══════════════════\n\n";

// Username
echo "Enter admin username: ";
$handle = fopen("php://stdin", "r");
$username = trim(fgets($handle));
fclose($handle);

if (empty($username)) {
    echo "❌ Error: Username cannot be empty.\n";
    exit(1);
}

// Check if username exists
$stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE username = ?");
$stmt->execute([$username]);
$result = $stmt->fetch();

if ($result['count'] > 0) {
    echo "❌ Error: Username '$username' already exists.\n";
    exit(1);
}

// Email
echo "Enter admin email: ";
$handle = fopen("php://stdin", "r");
$email = trim(fgets($handle));
fclose($handle);

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Error: Invalid email address.\n";
    exit(1);
}

// Check if email exists
$stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
$stmt->execute([$email]);
$result = $stmt->fetch();

if ($result['count'] > 0) {
    echo "❌ Error: Email '$email' already exists.\n";
    exit(1);
}

// Password (hidden input)
echo "Enter admin password: ";
system('stty -echo');
$handle = fopen("php://stdin", "r");
$password = trim(fgets($handle));
fclose($handle);
system('stty echo');
echo "\n";

if (strlen($password) < 8) {
    echo "❌ Error: Password must be at least 8 characters long.\n";
    exit(1);
}

// Confirm password
echo "Confirm password: ";
system('stty -echo');
$handle = fopen("php://stdin", "r");
$passwordConfirm = trim(fgets($handle));
fclose($handle);
system('stty echo');
echo "\n";

if ($password !== $passwordConfirm) {
    echo "❌ Error: Passwords do not match.\n";
    exit(1);
}

// Create admin user
echo "\nCreating admin user...\n";

try {
    $passwordHash = hashPassword($password);
    
    $stmt = $db->prepare("
        INSERT INTO users (username, email, password_hash, role)
        VALUES (?, ?, ?, 'admin')
    ");
    
    $stmt->execute([$username, $email, $passwordHash]);
    
    echo "✅ Admin user created successfully!\n\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "Login Credentials:\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "Username: $username\n";
    echo "Email: $email\n";
    echo "Password: [hidden]\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    echo "You can now log in to the admin panel at:\n";
    echo SITE_URL . "/admin/\n\n";
    echo "⚠️  Important: Please keep these credentials secure!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error creating admin user: " . $e->getMessage() . "\n";
    exit(1);
}

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
    echo "✅ Created logs directory\n";
}

// Check uploads directory
if (!file_exists(__DIR__ . '/uploads')) {
    mkdir(__DIR__ . '/uploads', 0755, true);
    echo "✅ Created uploads directory\n";
} else {
    echo "✅ Uploads directory exists\n";
}

// Set proper permissions
chmod(__DIR__ . '/uploads', 0755);
echo "✅ Set uploads directory permissions\n";

if (file_exists(__DIR__ . '/logs')) {
    chmod(__DIR__ . '/logs', 0755);
    echo "✅ Set logs directory permissions\n";
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║           Installation Completed Successfully!          ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Next steps:\n";
echo "1. Configure your web server (Apache/Nginx)\n";
echo "2. Set up HTTPS with Let's Encrypt\n";
echo "3. Update .htaccess to force HTTPS (uncomment lines)\n";
echo "4. Get a TinyMCE API key and update admin/posts/create.php\n";
echo "5. Log in to the admin panel and start creating content!\n";
echo "\n";
