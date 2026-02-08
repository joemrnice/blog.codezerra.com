<?php
/**
 * Posts List - Admin
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

setSecurityHeaders();
requireLogin();

// Get all posts
$posts = getAllPosts(null, 100, 0);
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="flex">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-8">
            <div class="max-w-7xl mx-auto">
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Posts</h1>
                    <a href="/admin/posts/create.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition">
                        + New Post
                    </a>
                </div>
                
                <?php if ($flash): ?>
                    <div class="bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-100 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-400 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-700 px-4 py-3 rounded mb-6">
                        <?php echo escape($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Author</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Views</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($posts)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        <p class="mb-4">No posts yet. Create your first post!</p>
                                        <a href="/admin/posts/create.php" class="text-purple-600 hover:text-purple-800 font-medium">Create Post →</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo escape($post['title']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo escape($post['slug']); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs rounded-full <?php echo $post['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                <?php echo escape(ucfirst($post['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500"><?php echo escape($post['author_name']); ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-500"><?php echo $post['views']; ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-500"><?php echo formatDate($post['created_at'], 'M d, Y'); ?></td>
                                        <td class="px-6 py-4 text-sm">
                                            <a href="/admin/posts/edit.php?id=<?php echo $post['id']; ?>" class="text-blue-600 hover:text-blue-800 mr-3">Edit</a>
                                            <a href="/post.php?slug=<?php echo $post['slug']; ?>" class="text-green-600 hover:text-green-800 mr-3" target="_blank">View</a>
                                            <a href="/admin/posts/delete.php?id=<?php echo $post['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this post?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
