<?php
/**
 * Sitemap Generator
 * Generates XML sitemap for search engines
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Set XML content type
header('Content-Type: application/xml; charset=utf-8');

// Generate and output sitemap
echo generateSitemap();
