<?php
/**
 * Database Functions
 * All database queries and operations
 */

require_once __DIR__ . '/config.php';

// ============ USER FUNCTIONS ============

function createUser($username, $email, $password, $role = 'author') {
    $db = getDBConnection();
    $passwordHash = hashPassword($password);
    
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$username, $email, $passwordHash, $role]);
}

function getUserByUsername($username) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

function getUserById($id) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ============ POST FUNCTIONS ============

function createPost($data) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        INSERT INTO posts (title, slug, content, excerpt, featured_image, author_id, status, published_at, meta_title, meta_description, og_image, og_description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $data['title'],
        $data['slug'],
        $data['content'],
        $data['excerpt'] ?? null,
        $data['featured_image'] ?? null,
        $data['author_id'],
        $data['status'] ?? 'draft',
        $data['published_at'] ?? null,
        $data['meta_title'] ?? null,
        $data['meta_description'] ?? null,
        $data['og_image'] ?? null,
        $data['og_description'] ?? null
    ]);
}

function updatePost($id, $data) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        UPDATE posts SET 
            title = ?, slug = ?, content = ?, excerpt = ?, featured_image = ?, 
            status = ?, published_at = ?, meta_title = ?, meta_description = ?, 
            og_image = ?, og_description = ?
        WHERE id = ?
    ");
    
    return $stmt->execute([
        $data['title'],
        $data['slug'],
        $data['content'],
        $data['excerpt'] ?? null,
        $data['featured_image'] ?? null,
        $data['status'] ?? 'draft',
        $data['published_at'] ?? null,
        $data['meta_title'] ?? null,
        $data['meta_description'] ?? null,
        $data['og_image'] ?? null,
        $data['og_description'] ?? null,
        $id
    ]);
}

function deletePost($id) {
    $db = getDBConnection();
    $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
    return $stmt->execute([$id]);
}

function getPostById($id) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT p.*, u.username as author_name, u.email as author_email
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        WHERE p.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getPostBySlug($slug) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT p.*, u.username as author_name, u.email as author_email
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        WHERE p.slug = ?
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function getAllPosts($status = null, $limit = 10, $offset = 0) {
    $db = getDBConnection();
    
    if ($status) {
        $stmt = $db->prepare("
            SELECT p.*, u.username as author_name
            FROM posts p
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.status = ?
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$status, $limit, $offset]);
    } else {
        $stmt = $db->prepare("
            SELECT p.*, u.username as author_name
            FROM posts p
            LEFT JOIN users u ON p.author_id = u.id
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
    }
    
    return $stmt->fetchAll();
}

function getPublishedPosts($limit = 10, $offset = 0) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT p.*, u.username as author_name
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        WHERE p.status = 'published' AND (p.published_at IS NULL OR p.published_at <= NOW())
        ORDER BY p.published_at DESC, p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

function getPostCount($status = null) {
    $db = getDBConnection();
    
    if ($status) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM posts WHERE status = ?");
        $stmt->execute([$status]);
    } else {
        $stmt = $db->query("SELECT COUNT(*) as count FROM posts");
    }
    
    $result = $stmt->fetch();
    return $result['count'];
}

function incrementPostViews($id) {
    $db = getDBConnection();
    $stmt = $db->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
    return $stmt->execute([$id]);
}

function searchPosts($query, $limit = 10) {
    $db = getDBConnection();
    $searchTerm = "%$query%";
    
    $stmt = $db->prepare("
        SELECT p.*, u.username as author_name
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        WHERE p.status = 'published' 
        AND (p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)
        ORDER BY p.published_at DESC
        LIMIT ?
    ");
    
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
    return $stmt->fetchAll();
}

// ============ CATEGORY FUNCTIONS ============

function createCategory($name, $slug, $description = null) {
    $db = getDBConnection();
    $stmt = $db->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
    return $stmt->execute([$name, $slug, $description]);
}

function getAllCategories() {
    $db = getDBConnection();
    $stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
    return $stmt->fetchAll();
}

function getCategoryById($id) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getPostCategories($postId) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT c.*
        FROM categories c
        INNER JOIN post_categories pc ON c.id = pc.category_id
        WHERE pc.post_id = ?
    ");
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

function assignCategoriesToPost($postId, $categoryIds) {
    $db = getDBConnection();
    
    // Remove existing categories
    $stmt = $db->prepare("DELETE FROM post_categories WHERE post_id = ?");
    $stmt->execute([$postId]);
    
    // Add new categories
    if (!empty($categoryIds)) {
        $stmt = $db->prepare("INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)");
        foreach ($categoryIds as $categoryId) {
            $stmt->execute([$postId, $categoryId]);
        }
    }
    
    return true;
}

// ============ TAG FUNCTIONS ============

function createTag($name, $slug) {
    $db = getDBConnection();
    $stmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
    return $stmt->execute([$name, $slug]);
}

function getAllTags() {
    $db = getDBConnection();
    $stmt = $db->query("SELECT * FROM tags ORDER BY name ASC");
    return $stmt->fetchAll();
}

function getPostTags($postId) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT t.*
        FROM tags t
        INNER JOIN post_tags pt ON t.id = pt.tag_id
        WHERE pt.post_id = ?
    ");
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

function assignTagsToPost($postId, $tagIds) {
    $db = getDBConnection();
    
    // Remove existing tags
    $stmt = $db->prepare("DELETE FROM post_tags WHERE post_id = ?");
    $stmt->execute([$postId]);
    
    // Add new tags
    if (!empty($tagIds)) {
        $stmt = $db->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
        foreach ($tagIds as $tagId) {
            $stmt->execute([$postId, $tagId]);
        }
    }
    
    return true;
}

// ============ MEDIA FUNCTIONS ============

function saveMedia($filename, $originalFilename, $filePath, $fileType, $fileSize, $uploadedBy, $altText = null) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        INSERT INTO media (filename, original_filename, file_path, file_type, file_size, alt_text, uploaded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $filename,
        $originalFilename,
        $filePath,
        $fileType,
        $fileSize,
        $altText,
        $uploadedBy
    ]);
}

function getAllMedia($limit = 50, $offset = 0) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT m.*, u.username as uploaded_by_name
        FROM media m
        LEFT JOIN users u ON m.uploaded_by = u.id
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

function deleteMedia($id) {
    $db = getDBConnection();
    $stmt = $db->prepare("DELETE FROM media WHERE id = ?");
    return $stmt->execute([$id]);
}

// ============ SETTINGS FUNCTIONS ============

function getSetting($key, $default = null) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    
    return $result ? $result['setting_value'] : $default;
}

function updateSetting($key, $value) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        INSERT INTO settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = CURRENT_TIMESTAMP
    ");
    
    return $stmt->execute([$key, $value, $value]);
}

function getAllSettings() {
    $db = getDBConnection();
    $stmt = $db->query("SELECT * FROM settings");
    $results = $stmt->fetchAll();
    
    $settings = [];
    foreach ($results as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    return $settings;
}

// ============ SESSION FUNCTIONS ============

function createSession($userId, $sessionToken, $expiresAt) {
    $db = getDBConnection();
    $sessionId = session_id();
    
    $stmt = $db->prepare("
        INSERT INTO sessions (id, user_id, session_token, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    
    return $stmt->execute([$sessionId, $userId, $sessionToken, $expiresAt]);
}

function getSession($sessionToken) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT * FROM sessions 
        WHERE session_token = ? AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$sessionToken]);
    return $stmt->fetch();
}

function deleteSession($sessionToken) {
    $db = getDBConnection();
    $stmt = $db->prepare("DELETE FROM sessions WHERE session_token = ?");
    return $stmt->execute([$sessionToken]);
}

// ============ CHATBOT FUNCTIONS ============

function saveChatMessage($sessionId, $userMessage, $botResponse) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        INSERT INTO chatbot_conversations (session_id, user_message, bot_response)
        VALUES (?, ?, ?)
    ");
    
    return $stmt->execute([$sessionId, $userMessage, $botResponse]);
}

function getChatHistory($sessionId, $limit = 20) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT * FROM chatbot_conversations
        WHERE session_id = ?
        ORDER BY created_at DESC
        LIMIT ?
    ");
    
    $stmt->execute([$sessionId, $limit]);
    return array_reverse($stmt->fetchAll());
}
