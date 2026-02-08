<?php
/**
 * Admin Login Page
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

setSecurityHeaders();

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/admin/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check rate limiting
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!checkRateLimit('login_' . $ip, 5, 300)) {
        $error = 'Too many login attempts. Please try again later.';
        logSecurityEvent('Login rate limit exceeded', "IP: $ip");
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        // Verify CSRF token
        if (!verifyCSRFToken($csrfToken)) {
            $error = 'Invalid request. Please try again.';
            logSecurityEvent('CSRF token validation failed', "Username: $username");
        } else {
            $user = getUserByUsername($username);
            
            if ($user && verifyPassword($password, $user['password_hash'])) {
                // Successful login
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                
                // Create session in database
                $sessionToken = generateSecureToken();
                $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
                createSession($user['id'], $sessionToken, $expiresAt);
                
                $_SESSION['session_token'] = $sessionToken;
                
                logSecurityEvent('Successful login', "User: $username");
                
                redirect('/admin/index.php');
            } else {
                $error = 'Invalid username or password';
                logSecurityEvent('Failed login attempt', "Username: $username, IP: $ip");
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - CodeZerra Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-2xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">CodeZerra Blog</h1>
                <p class="text-gray-600">Admin Login</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                    <span class="block sm:inline"><?php echo escape($error); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo escape($csrfToken); ?>">
                
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                        placeholder="Enter your username"
                        autocomplete="username"
                    >
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                    >
                </div>
                
                <button 
                    type="submit" 
                    class="w-full bg-purple-600 text-white py-3 rounded-lg font-semibold hover:bg-purple-700 transition duration-200 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2"
                >
                    Login
                </button>
            </form>
            
            <div class="mt-6 text-center text-sm text-gray-600">
                <a href="/" class="text-purple-600 hover:text-purple-800">← Back to Website</a>
            </div>
        </div>
        
        <div class="text-center mt-4 text-white text-sm">
            <p>Protected by security measures including rate limiting and CSRF protection</p>
        </div>
    </div>
</body>
</html>
