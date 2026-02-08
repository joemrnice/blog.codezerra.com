<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

setSecurityHeaders();

$settings = getAllSettings();
$siteTitle = $settings['site_title'] ?? 'CodeZerra Blog';

// Get search query
$query = $_GET['q'] ?? '';
$query = sanitizeInput($query);

$results = [];
if (!empty($query) && strlen($query) >= 2) {
    $results = searchPosts($query, 50);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results for "<?php echo escape($query); ?>" - <?php echo escape($siteTitle); ?></title>
    <meta name="description" content="Search results for <?php echo escape($query); ?>">
    <meta name="robots" content="noindex, nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-blue-600"><?php echo escape($siteTitle); ?></a>
                </div>
                <div class="flex items-center space-x-8">
                    <a href="/" class="text-gray-700 hover:text-blue-600 font-medium">Home</a>
                    <a href="/blog.php" class="text-gray-700 hover:text-blue-600 font-medium">Blog</a>
                    <a href="/about.php" class="text-gray-700 hover:text-blue-600 font-medium">About</a>
                    <a href="/contact.php" class="text-gray-700 hover:text-blue-600 font-medium">Contact</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold mb-4">Search Results</h1>
            <?php if (!empty($query)): ?>
                <p class="text-blue-100">
                    Showing results for: <strong>"<?php echo escape($query); ?>"</strong>
                </p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Content -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Search Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form action="/search.php" method="get" class="flex">
                <input type="text" 
                       name="q" 
                       value="<?php echo escape($query); ?>"
                       placeholder="Search posts..." 
                       class="flex-1 px-4 py-3 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-600">
                <button type="submit" 
                        class="px-6 py-3 bg-blue-600 text-white rounded-r-md hover:bg-blue-700 font-medium">
                    Search
                </button>
            </form>
        </div>

        <?php if (empty($query)): ?>
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Start Searching</h2>
                <p class="text-gray-600">Enter a search term to find blog posts.</p>
            </div>
        <?php elseif (strlen($query) < 2): ?>
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <p class="text-gray-600">Please enter at least 2 characters to search.</p>
            </div>
        <?php elseif (empty($results)): ?>
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">No Results Found</h2>
                <p class="text-gray-600 mb-6">
                    We couldn't find any posts matching "<strong><?php echo escape($query); ?></strong>".
                </p>
                <a href="/blog.php" class="text-blue-600 hover:text-blue-800 font-medium">
                    Browse all posts →
                </a>
            </div>
        <?php else: ?>
            <div class="mb-6">
                <p class="text-gray-600">
                    Found <strong><?php echo count($results); ?></strong> result<?php echo count($results) !== 1 ? 's' : ''; ?>
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($results as $post): ?>
                    <?php 
                        $excerpt = $post['excerpt'] ?? getExcerpt($post['content'], 150);
                        $readingTime = calculateReadingTime($post['content']);
                    ?>
                    <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                        <?php if ($post['featured_image']): ?>
                            <a href="<?php echo escape(getPostUrl($post['slug'])); ?>">
                                <img src="<?php echo escape(getUploadUrl($post['featured_image'])); ?>" 
                                     alt="<?php echo escape($post['title']); ?>"
                                     class="w-full h-48 object-cover">
                            </a>
                        <?php else: ?>
                            <div class="w-full h-48 bg-gradient-to-r from-blue-400 to-purple-500"></div>
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-2">
                                <a href="<?php echo escape(getPostUrl($post['slug'])); ?>" 
                                   class="text-gray-900 hover:text-blue-600">
                                    <?php echo escape($post['title']); ?>
                                </a>
                            </h3>
                            
                            <p class="text-gray-600 text-sm mb-4"><?php echo escape($excerpt); ?></p>
                            
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span><?php echo escape($post['author_name'] ?? 'Admin'); ?></span>
                                <span><?php echo $readingTime; ?> min read</span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4"><?php echo escape($siteTitle); ?></h3>
                    <p class="text-gray-400"><?php echo escape($settings['site_description'] ?? ''); ?></p>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="/" class="text-gray-400 hover:text-white">Home</a></li>
                        <li><a href="/blog.php" class="text-gray-400 hover:text-white">Blog</a></li>
                        <li><a href="/about.php" class="text-gray-400 hover:text-white">About</a></li>
                        <li><a href="/contact.php" class="text-gray-400 hover:text-white">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Connect</h4>
                    <div class="flex space-x-4">
                        <?php if (!empty($settings['social_twitter'])): ?>
                            <a href="<?php echo escape($settings['social_twitter']); ?>" class="text-gray-400 hover:text-white">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($settings['social_github'])): ?>
                            <a href="<?php echo escape($settings['social_github']); ?>" class="text-gray-400 hover:text-white">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($settings['social_linkedin'])): ?>
                            <a href="<?php echo escape($settings['social_linkedin']); ?>" class="text-gray-400 hover:text-white">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> <?php echo escape($siteTitle); ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
