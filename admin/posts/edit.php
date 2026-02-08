<?php
/**
 * Edit Post - Admin
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

setSecurityHeaders();
requireLogin();

$csrf_token = generateCSRFToken();
$errors = [];
$post = null;

// Get post ID from URL
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($postId <= 0) {
    setFlashMessage('error', 'Invalid post ID.');
    redirect('/admin/posts/index.php');
}

// Load the post
$post = getPostById($postId);

if (!$post) {
    setFlashMessage('error', 'Post not found.');
    redirect('/admin/posts/index.php');
}

// Load categories and tags
$categories = getAllCategories();
$tags = getAllTags();
$postCategories = array_column(getPostCategories($postId), 'id');
$postTags = array_column(getPostTags($postId), 'id');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid CSRF token. Please try again.';
    } else {
        // Sanitize input
        $formData = sanitizeInput($_POST);
        
        // Validate required fields
        if (empty($formData['title'])) {
            $errors[] = 'Title is required.';
        }
        if (empty($formData['slug'])) {
            $errors[] = 'Slug is required.';
        }
        if (empty($formData['content'])) {
            $errors[] = 'Content is required.';
        }
        
        // If no errors, update the post
        if (empty($errors)) {
            try {
                $db = getDBConnection();
                $db->beginTransaction();
                
                // Prepare post data
                $postData = [
                    'title' => $formData['title'],
                    'slug' => $formData['slug'],
                    'content' => $formData['content'],
                    'excerpt' => $formData['excerpt'] ?? null,
                    'featured_image' => $formData['featured_image'] ?? null,
                    'status' => $formData['status'] ?? 'draft',
                    'published_at' => !empty($formData['published_at']) ? $formData['published_at'] : null,
                    'meta_title' => $formData['meta_title'] ?? null,
                    'meta_description' => $formData['meta_description'] ?? null,
                    'og_image' => $formData['og_image'] ?? null,
                    'og_description' => $formData['og_description'] ?? null
                ];
                
                // Update post
                if (updatePost($postId, $postData)) {
                    // Update categories
                    if (isset($formData['categories'])) {
                        assignCategoriesToPost($postId, $formData['categories']);
                    } else {
                        assignCategoriesToPost($postId, []);
                    }
                    
                    // Update tags
                    if (isset($formData['tags'])) {
                        assignTagsToPost($postId, $formData['tags']);
                    } else {
                        assignTagsToPost($postId, []);
                    }
                    
                    $db->commit();
                    
                    setFlashMessage('success', 'Post updated successfully!');
                    redirect('/admin/posts/index.php');
                } else {
                    $db->rollBack();
                    $errors[] = 'Failed to update post. Please try again.';
                }
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
        
        // Update post array with form data for re-display
        $post = array_merge($post, $formData);
        $postCategories = $formData['categories'] ?? [];
        $postTags = $formData['tags'] ?? [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Note: Replace 'no-api-key' with your TinyMCE API key for production use -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="flex">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-8">
            <div class="max-w-5xl mx-auto">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Edit Post</h1>
                    <p class="text-gray-600 mt-2">Update your blog post</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo escape($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <!-- Main Content Card -->
                    <div class="bg-white rounded-lg shadow p-6 space-y-6">
                        <h2 class="text-xl font-semibold text-gray-800 border-b pb-3">Post Content</h2>
                        
                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                            <input type="text" id="title" name="title" required
                                   value="<?php echo escape($post['title']); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        
                        <!-- Slug -->
                        <div>
                            <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Slug *</label>
                            <input type="text" id="slug" name="slug" required
                                   value="<?php echo escape($post['slug']); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <p class="text-sm text-gray-500 mt-1">URL-friendly version of the title</p>
                        </div>
                        
                        <!-- Content -->
                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Content *</label>
                            <textarea id="content" name="content" rows="15" required
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"><?php echo escape($post['content']); ?></textarea>
                        </div>
                        
                        <!-- Excerpt -->
                        <div>
                            <label for="excerpt" class="block text-sm font-medium text-gray-700 mb-2">Excerpt</label>
                            <textarea id="excerpt" name="excerpt" rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                      placeholder="Optional short description..."><?php echo escape($post['excerpt'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Featured Image & Status -->
                    <div class="bg-white rounded-lg shadow p-6 space-y-6">
                        <h2 class="text-xl font-semibold text-gray-800 border-b pb-3">Post Settings</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Featured Image -->
                            <div>
                                <label for="featured_image" class="block text-sm font-medium text-gray-700 mb-2">Featured Image URL</label>
                                <input type="text" id="featured_image" name="featured_image"
                                       value="<?php echo escape($post['featured_image'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                       placeholder="/uploads/image.jpg">
                            </div>
                            
                            <!-- Status -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select id="status" name="status"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    <option value="draft" <?php echo $post['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="published" <?php echo $post['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                    <option value="scheduled" <?php echo $post['status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Published At -->
                        <div>
                            <label for="published_at" class="block text-sm font-medium text-gray-700 mb-2">Published At</label>
                            <input type="datetime-local" id="published_at" name="published_at"
                                   value="<?php echo $post['published_at'] ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : ''; ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <p class="text-sm text-gray-500 mt-1">Leave empty to use current time for published posts</p>
                        </div>
                    </div>
                    
                    <!-- Categories & Tags -->
                    <div class="bg-white rounded-lg shadow p-6 space-y-6">
                        <h2 class="text-xl font-semibold text-gray-800 border-b pb-3">Categories & Tags</h2>
                        
                        <!-- Categories -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Categories</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                <?php foreach ($categories as $category): ?>
                                    <label class="flex items-center space-x-2 p-2 border border-gray-200 rounded hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>"
                                               <?php echo in_array($category['id'], $postCategories) ? 'checked' : ''; ?>
                                               class="rounded text-purple-600 focus:ring-purple-500">
                                        <span class="text-sm text-gray-700"><?php echo escape($category['name']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Tags -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Tags</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                <?php foreach ($tags as $tag): ?>
                                    <label class="flex items-center space-x-2 p-2 border border-gray-200 rounded hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>"
                                               <?php echo in_array($tag['id'], $postTags) ? 'checked' : ''; ?>
                                               class="rounded text-purple-600 focus:ring-purple-500">
                                        <span class="text-sm text-gray-700"><?php echo escape($tag['name']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SEO Meta Fields -->
                    <div class="bg-white rounded-lg shadow p-6 space-y-6">
                        <h2 class="text-xl font-semibold text-gray-800 border-b pb-3">SEO & Social Media</h2>
                        
                        <!-- Meta Title -->
                        <div>
                            <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                            <input type="text" id="meta_title" name="meta_title"
                                   value="<?php echo escape($post['meta_title'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                   placeholder="SEO title (optional, defaults to post title)">
                        </div>
                        
                        <!-- Meta Description -->
                        <div>
                            <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                            <textarea id="meta_description" name="meta_description" rows="2"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                      placeholder="SEO description (optional)"><?php echo escape($post['meta_description'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- OG Image -->
                        <div>
                            <label for="og_image" class="block text-sm font-medium text-gray-700 mb-2">Open Graph Image</label>
                            <input type="text" id="og_image" name="og_image"
                                   value="<?php echo escape($post['og_image'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                   placeholder="Social media image URL (optional)">
                        </div>
                        
                        <!-- OG Description -->
                        <div>
                            <label for="og_description" class="block text-sm font-medium text-gray-700 mb-2">Open Graph Description</label>
                            <textarea id="og_description" name="og_description" rows="2"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                      placeholder="Social media description (optional)"><?php echo escape($post['og_description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex justify-between items-center">
                        <a href="/admin/posts/index.php" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <div class="space-x-3">
                            <button type="submit"
                                    class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                                Update Post
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
        // Auto-generate slug from title (only on first edit, not every keystroke)
        let originalSlug = document.getElementById('slug').value;
        let slugModified = originalSlug !== '';
        
        document.getElementById('title').addEventListener('blur', function(e) {
            // Only auto-generate if slug hasn't been manually modified
            if (!slugModified) {
                const title = e.target.value;
                const slug = title
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
                document.getElementById('slug').value = slug;
            }
        });
        
        // Track manual slug modifications
        document.getElementById('slug').addEventListener('input', function() {
            slugModified = true;
        });
        
        // Initialize TinyMCE
        tinymce.init({
            selector: '#content',
            height: 500,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
                'bold italic forecolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | link image | code | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
        });
    </script>
</body>
</html>
