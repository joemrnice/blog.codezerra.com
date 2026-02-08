<?php
/**
 * Helper Functions
 * Utility functions used throughout the application
 */

require_once __DIR__ . '/security.php';

/**
 * Generate slug from string
 */
function generateSlug($string) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
    return $slug;
}

/**
 * Format date
 */
function formatDate($date, $format = 'F j, Y') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

/**
 * Calculate reading time
 */
function calculateReadingTime($content) {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = ceil($wordCount / 200); // Average reading speed: 200 words per minute
    return $minutes;
}

/**
 * Truncate text
 */
function truncateText($text, $length = 150, $suffix = '...') {
    $text = strip_tags($text);
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Get excerpt from content
 */
function getExcerpt($content, $length = 200) {
    return truncateText($content, $length);
}

/**
 * Redirect function
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Flash message system
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Pagination helper
 */
function getPagination($currentPage, $totalItems, $itemsPerPage, $baseUrl) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    
    $pagination = [
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'total_items' => $totalItems,
        'items_per_page' => $itemsPerPage,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'prev_url' => $currentPage > 1 ? $baseUrl . '?page=' . ($currentPage - 1) : null,
        'next_url' => $currentPage < $totalPages ? $baseUrl . '?page=' . ($currentPage + 1) : null,
        'pages' => []
    ];
    
    // Generate page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $pagination['pages'][] = [
            'number' => $i,
            'url' => $baseUrl . '?page=' . $i,
            'is_current' => $i === $currentPage
        ];
    }
    
    return $pagination;
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Get relative time (e.g., "2 hours ago")
 */
function getRelativeTime($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return formatDate($timestamp);
    }
}

/**
 * Get post URL
 */
function getPostUrl($slug) {
    return SITE_URL . '/post.php?slug=' . urlencode($slug);
}

/**
 * Get category URL
 */
function getCategoryUrl($slug) {
    return SITE_URL . '/blog.php?category=' . urlencode($slug);
}

/**
 * Get tag URL
 */
function getTagUrl($slug) {
    return SITE_URL . '/blog.php?tag=' . urlencode($slug);
}

/**
 * Get asset URL
 */
function getAssetUrl($path) {
    return SITE_URL . '/assets/' . ltrim($path, '/');
}

/**
 * Get upload URL
 */
function getUploadUrl($filename) {
    return SITE_URL . '/uploads/' . $filename;
}

/**
 * Current URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * JSON response helper
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Generate sitemap
 */
function generateSitemap() {
    require_once __DIR__ . '/db.php';
    
    $posts = getPublishedPosts(1000, 0);
    
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    // Homepage
    $xml .= '  <url>' . "\n";
    $xml .= '    <loc>' . SITE_URL . '/</loc>' . "\n";
    $xml .= '    <changefreq>daily</changefreq>' . "\n";
    $xml .= '    <priority>1.0</priority>' . "\n";
    $xml .= '  </url>' . "\n";
    
    // Blog posts
    foreach ($posts as $post) {
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . escape(getPostUrl($post['slug'])) . '</loc>' . "\n";
        $xml .= '    <lastmod>' . date('Y-m-d', strtotime($post['updated_at'])) . '</lastmod>' . "\n";
        $xml .= '    <changefreq>weekly</changefreq>' . "\n";
        $xml .= '    <priority>0.8</priority>' . "\n";
        $xml .= '  </url>' . "\n";
    }
    
    // Static pages
    $staticPages = ['about.php', 'blog.php', 'contact.php'];
    foreach ($staticPages as $page) {
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . SITE_URL . '/' . $page . '</loc>' . "\n";
        $xml .= '    <changefreq>monthly</changefreq>' . "\n";
        $xml .= '    <priority>0.6</priority>' . "\n";
        $xml .= '  </url>' . "\n";
    }
    
    $xml .= '</urlset>';
    
    return $xml;
}

/**
 * Get meta tags for a post
 */
function getPostMetaTags($post) {
    $metaTitle = !empty($post['meta_title']) ? $post['meta_title'] : $post['title'];
    $metaDescription = !empty($post['meta_description']) ? $post['meta_description'] : truncateText($post['excerpt'] ?? $post['content'], 160);
    $ogImage = !empty($post['og_image']) ? $post['og_image'] : $post['featured_image'];
    $ogDescription = !empty($post['og_description']) ? $post['og_description'] : $metaDescription;
    
    return [
        'title' => $metaTitle,
        'description' => $metaDescription,
        'og_image' => $ogImage,
        'og_description' => $ogDescription,
        'url' => getPostUrl($post['slug'])
    ];
}

/**
 * Generate JSON-LD schema for article
 */
function getArticleSchema($post) {
    $meta = getPostMetaTags($post);
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $post['title'],
        'description' => $meta['description'],
        'author' => [
            '@type' => 'Person',
            'name' => $post['author_name'] ?? 'Admin'
        ],
        'datePublished' => $post['published_at'] ?? $post['created_at'],
        'dateModified' => $post['updated_at'],
        'publisher' => [
            '@type' => 'Organization',
            'name' => getSetting('site_title', 'CodeZerra Blog'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => SITE_URL . '/assets/images/logo.png'
            ]
        ]
    ];
    
    if (!empty($post['featured_image'])) {
        $schema['image'] = getUploadUrl($post['featured_image']);
    }
    
    return json_encode($schema, JSON_UNESCAPED_SLASHES);
}
