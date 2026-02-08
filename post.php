<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

setSecurityHeaders();

$settings = getAllSettings();
$siteTitle = $settings['site_title'] ?? 'CodeZerra Blog';

// Get slug from URL
$slug = $_GET['slug'] ?? null;

if (!$slug) {
    header('Location: /blog.php');
    exit;
}

// Get post by slug
$post = getPostBySlug($slug);

if (!$post || $post['status'] !== 'published') {
    header('HTTP/1.0 404 Not Found');
    echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>Post Not Found</h1><p><a href="/blog.php">Back to blog</a></p></body></html>';
    exit;
}

// Increment view count
incrementPostViews($post['id']);

// Get post categories and tags
$categories = getPostCategories($post['id']);
$tags = getPostTags($post['id']);

// Get related posts (same category)
$relatedPosts = [];
if (!empty($categories)) {
    $db = getDBConnection();
    $categoryId = $categories[0]['id'];
    $stmt = $db->prepare("
        SELECT DISTINCT p.*, u.username as author_name
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        INNER JOIN post_categories pc ON p.id = pc.post_id
        WHERE p.status = 'published' 
        AND pc.category_id = ? 
        AND p.id != ?
        ORDER BY p.published_at DESC
        LIMIT 3
    ");
    $stmt->execute([$categoryId, $post['id']]);
    $relatedPosts = $stmt->fetchAll();
}

// Generate meta tags
$meta = getPostMetaTags($post);
$readingTime = calculateReadingTime($post['content']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($meta['title']); ?> - <?php echo escape($siteTitle); ?></title>
    <meta name="description" content="<?php echo escape($meta['description']); ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo escape($meta['url']); ?>">
    <meta property="og:title" content="<?php echo escape($meta['title']); ?>">
    <meta property="og:description" content="<?php echo escape($meta['og_description']); ?>">
    <?php if ($meta['og_image']): ?>
        <meta property="og:image" content="<?php echo escape(getUploadUrl($meta['og_image'])); ?>">
    <?php endif; ?>
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo escape($meta['url']); ?>">
    <meta property="twitter:title" content="<?php echo escape($meta['title']); ?>">
    <meta property="twitter:description" content="<?php echo escape($meta['og_description']); ?>">
    <?php if ($meta['og_image']): ?>
        <meta property="twitter:image" content="<?php echo escape(getUploadUrl($meta['og_image'])); ?>">
    <?php endif; ?>
    
    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    <?php echo getArticleSchema($post); ?>
    </script>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .post-content {
            line-height: 1.8;
        }
        .post-content h2 {
            font-size: 1.875rem;
            font-weight: bold;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .post-content h3 {
            font-size: 1.5rem;
            font-weight: bold;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .post-content p {
            margin-bottom: 1rem;
        }
        .post-content ul, .post-content ol {
            margin-bottom: 1rem;
            padding-left: 2rem;
        }
        .post-content li {
            margin-bottom: 0.5rem;
        }
        .post-content code {
            background-color: #f3f4f6;
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
            font-family: monospace;
        }
        .post-content pre {
            background-color: #1f2937;
            color: #f9fafb;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin-bottom: 1rem;
        }
        .post-content pre code {
            background-color: transparent;
            color: inherit;
            padding: 0;
        }
        .post-content blockquote {
            border-left: 4px solid #3b82f6;
            padding-left: 1rem;
            margin: 1rem 0;
            font-style: italic;
            color: #4b5563;
        }
        .post-content img {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }
    </style>
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

    <!-- Breadcrumb -->
    <div class="bg-white border-b">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="text-sm text-gray-500">
                <a href="/" class="hover:text-blue-600">Home</a>
                <span class="mx-2">/</span>
                <a href="/blog.php" class="hover:text-blue-600">Blog</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900"><?php echo escape($post['title']); ?></span>
            </nav>
        </div>
    </div>

    <!-- Article -->
    <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Post Header -->
        <header class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-4"><?php echo escape($post['title']); ?></h1>
            
            <div class="flex items-center text-gray-600 mb-6">
                <div class="flex items-center space-x-4">
                    <span class="font-medium"><?php echo escape($post['author_name'] ?? 'Admin'); ?></span>
                    <span>•</span>
                    <time datetime="<?php echo escape($post['published_at'] ?? $post['created_at']); ?>">
                        <?php echo escape(formatDate($post['published_at'] ?? $post['created_at'])); ?>
                    </time>
                    <span>•</span>
                    <span><?php echo $readingTime; ?> min read</span>
                    <span>•</span>
                    <span><?php echo number_format($post['views']); ?> views</span>
                </div>
            </div>
            
            <!-- Categories and Tags -->
            <div class="flex flex-wrap gap-4 mb-6">
                <?php if (!empty($categories)): ?>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($categories as $category): ?>
                            <a href="<?php echo escape(getCategoryUrl($category['slug'])); ?>" 
                               class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm hover:bg-blue-200">
                                <?php echo escape($category['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($tags)): ?>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($tags as $tag): ?>
                            <a href="<?php echo escape(getTagUrl($tag['slug'])); ?>" 
                               class="px-3 py-1 bg-gray-200 text-gray-700 rounded-full text-sm hover:bg-gray-300">
                                #<?php echo escape($tag['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- Featured Image -->
        <?php if ($post['featured_image']): ?>
            <div class="mb-8">
                <img src="<?php echo escape(getUploadUrl($post['featured_image'])); ?>" 
                     alt="<?php echo escape($post['title']); ?>"
                     class="w-full rounded-lg shadow-lg">
            </div>
        <?php endif; ?>

        <!-- Post Content -->
        <!-- Note: Content is not escaped as it contains trusted HTML from admin panel -->
        <div class="post-content prose max-w-none bg-white rounded-lg shadow-md p-8 mb-12">
            <?php echo $post['content']; ?>
        </div>

        <!-- Author Info -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-12">
            <h3 class="text-lg font-bold mb-2">About the Author</h3>
            <p class="text-gray-600">
                Written by <span class="font-medium text-gray-900"><?php echo escape($post['author_name'] ?? 'Admin'); ?></span>
            </p>
        </div>

        <!-- Related Posts -->
        <?php if (!empty($relatedPosts)): ?>
            <div class="mb-12">
                <h2 class="text-2xl font-bold mb-6">Related Posts</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($relatedPosts as $relatedPost): ?>
                        <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                            <?php if ($relatedPost['featured_image']): ?>
                                <a href="<?php echo escape(getPostUrl($relatedPost['slug'])); ?>">
                                    <img src="<?php echo escape(getUploadUrl($relatedPost['featured_image'])); ?>" 
                                         alt="<?php echo escape($relatedPost['title']); ?>"
                                         class="w-full h-40 object-cover">
                                </a>
                            <?php else: ?>
                                <div class="w-full h-40 bg-gradient-to-r from-blue-400 to-purple-500"></div>
                            <?php endif; ?>
                            
                            <div class="p-4">
                                <h3 class="font-bold mb-2">
                                    <a href="<?php echo escape(getPostUrl($relatedPost['slug'])); ?>" 
                                       class="text-gray-900 hover:text-blue-600">
                                        <?php echo escape($relatedPost['title']); ?>
                                    </a>
                                </h3>
                                <p class="text-xs text-gray-500">
                                    <?php echo escape(formatDate($relatedPost['published_at'] ?? $relatedPost['created_at'])); ?>
                                </p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </article>

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
