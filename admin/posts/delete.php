<?php
/**
 * Delete Post - Admin
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

setSecurityHeaders();
requireLogin();

// Get post ID from URL
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($postId <= 0) {
    setFlashMessage('error', 'Invalid post ID.');
    redirect('/admin/posts/index.php');
}

// Verify CSRF token
if (!isset($_GET['csrf_token']) || !verifyCSRFToken($_GET['csrf_token'])) {
    setFlashMessage('error', 'Invalid CSRF token. Please try again.');
    redirect('/admin/posts/index.php');
}

// Check if post exists
$post = getPostById($postId);

if (!$post) {
    setFlashMessage('error', 'Post not found.');
    redirect('/admin/posts/index.php');
}

// Delete the post
try {
    if (deletePost($postId)) {
        setFlashMessage('success', 'Post "' . $post['title'] . '" deleted successfully!');
    } else {
        setFlashMessage('error', 'Failed to delete post. Please try again.');
    }
} catch (Exception $e) {
    setFlashMessage('error', 'Database error: ' . $e->getMessage());
}

redirect('/admin/posts/index.php');
