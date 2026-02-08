<?php
/**
 * Site Settings - Admin
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

setSecurityHeaders();
requireLogin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', 'Invalid CSRF token');
        redirect('/admin/settings/index.php');
    }
    
    // Define expected settings
    $settingsToUpdate = [
        'site_title',
        'site_description',
        'social_twitter',
        'social_github',
        'social_linkedin',
        'posts_per_page'
    ];
    
    $errors = [];
    $successCount = 0;
    
    // Validate posts_per_page
    if (isset($_POST['posts_per_page'])) {
        $postsPerPage = intval($_POST['posts_per_page']);
        if ($postsPerPage < 1 || $postsPerPage > 100) {
            $errors[] = 'Posts per page must be between 1 and 100';
        }
    }
    
    // Update each setting
    if (empty($errors)) {
        foreach ($settingsToUpdate as $key) {
            if (isset($_POST[$key])) {
                $value = sanitizeInput($_POST[$key]);
                
                // Additional validation for specific fields
                if ($key === 'posts_per_page') {
                    $value = intval($value);
                }
                
                if (updateSetting($key, $value)) {
                    $successCount++;
                } else {
                    $errors[] = "Failed to update $key";
                }
            }
        }
        
        if ($successCount > 0 && empty($errors)) {
            setFlashMessage('success', 'Settings updated successfully');
        } elseif (!empty($errors)) {
            setFlashMessage('error', 'Some settings failed to update: ' . implode(', ', $errors));
        }
    } else {
        setFlashMessage('error', implode(', ', $errors));
    }
    
    redirect('/admin/settings/index.php');
}

// Get all settings
$settings = getAllSettings();
$flash = getFlashMessage();
$csrfToken = generateCSRFToken();

// Set default values if settings don't exist
$defaults = [
    'site_title' => 'CodeZerra Blog',
    'site_description' => 'A tech blog about coding, development, and technology',
    'social_twitter' => '',
    'social_github' => '',
    'social_linkedin' => '',
    'posts_per_page' => '10'
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="flex">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-8">
            <div class="max-w-4xl mx-auto">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Site Settings</h1>
                    <p class="text-gray-600 mt-2">Configure your blog settings and preferences</p>
                </div>
                
                <?php if ($flash): ?>
                    <div class="bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-100 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-400 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-700 px-4 py-3 rounded mb-6">
                        <?php echo escape($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <!-- General Settings -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                            </svg>
                            General Settings
                        </h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="site_title" class="block text-sm font-medium text-gray-700 mb-2">
                                    Site Title *
                                </label>
                                <input type="text" id="site_title" name="site_title" required
                                       value="<?php echo escape($settings['site_title']); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                       placeholder="Your Blog Name">
                                <p class="text-sm text-gray-500 mt-1">The name of your blog</p>
                            </div>
                            
                            <div>
                                <label for="site_description" class="block text-sm font-medium text-gray-700 mb-2">
                                    Site Description *
                                </label>
                                <textarea id="site_description" name="site_description" required rows="3"
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                          placeholder="A brief description of your blog"><?php echo escape($settings['site_description']); ?></textarea>
                                <p class="text-sm text-gray-500 mt-1">Used in meta tags and site header</p>
                            </div>
                            
                            <div>
                                <label for="posts_per_page" class="block text-sm font-medium text-gray-700 mb-2">
                                    Posts Per Page *
                                </label>
                                <input type="number" id="posts_per_page" name="posts_per_page" required
                                       value="<?php echo escape($settings['posts_per_page']); ?>"
                                       min="1" max="100"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                       placeholder="10">
                                <p class="text-sm text-gray-500 mt-1">Number of posts to show per page (1-100)</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Media Settings -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Social Media Links
                        </h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="social_twitter" class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"></path>
                                        </svg>
                                        Twitter/X Username
                                    </span>
                                </label>
                                <input type="text" id="social_twitter" name="social_twitter"
                                       value="<?php echo escape($settings['social_twitter']); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                       placeholder="@username">
                                <p class="text-sm text-gray-500 mt-1">Your Twitter/X handle (optional)</p>
                            </div>
                            
                            <div>
                                <label for="social_github" class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"></path>
                                        </svg>
                                        GitHub Username
                                    </span>
                                </label>
                                <input type="text" id="social_github" name="social_github"
                                       value="<?php echo escape($settings['social_github']); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                       placeholder="username">
                                <p class="text-sm text-gray-500 mt-1">Your GitHub username (optional)</p>
                            </div>
                            
                            <div>
                                <label for="social_linkedin" class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"></path>
                                        </svg>
                                        LinkedIn Username
                                    </span>
                                </label>
                                <input type="text" id="social_linkedin" name="social_linkedin"
                                       value="<?php echo escape($settings['social_linkedin']); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                       placeholder="username">
                                <p class="text-sm text-gray-500 mt-1">Your LinkedIn username (optional)</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Save Button -->
                    <div class="flex justify-end gap-3">
                        <a href="/admin/index.php" 
                           class="px-8 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition font-medium">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-8 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-medium">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
