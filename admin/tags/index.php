<?php
/**
 * Tags Management - Admin
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

setSecurityHeaders();
requireLogin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', 'Invalid CSRF token');
        redirect('/admin/tags/index.php');
    }
    
    // Create new tag
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $name = sanitizeInput($_POST['name'] ?? '');
        
        if (empty($name)) {
            setFlashMessage('error', 'Tag name is required');
        } else {
            $slug = generateSlug($name);
            if (createTag($name, $slug)) {
                setFlashMessage('success', 'Tag created successfully');
            } else {
                setFlashMessage('error', 'Failed to create tag');
            }
        }
        redirect('/admin/tags/index.php');
    }
    
    // Update tag
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');
        
        if (empty($name) || $id <= 0) {
            setFlashMessage('error', 'Invalid tag data');
        } else {
            $slug = generateSlug($name);
            $db = getDBConnection();
            $stmt = $db->prepare("UPDATE tags SET name = ?, slug = ? WHERE id = ?");
            if ($stmt->execute([$name, $slug, $id])) {
                setFlashMessage('success', 'Tag updated successfully');
            } else {
                setFlashMessage('error', 'Failed to update tag');
            }
        }
        redirect('/admin/tags/index.php');
    }
    
    // Delete tag
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            setFlashMessage('error', 'Invalid tag ID');
        } else {
            $db = getDBConnection();
            $stmt = $db->prepare("DELETE FROM tags WHERE id = ?");
            if ($stmt->execute([$id])) {
                setFlashMessage('success', 'Tag deleted successfully');
            } else {
                setFlashMessage('error', 'Failed to delete tag');
            }
        }
        redirect('/admin/tags/index.php');
    }
}

// Get all tags
$tags = getAllTags();
$flash = getFlashMessage();
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tags - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="flex">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Tags</h1>
                    <p class="text-gray-600 mt-2">Manage blog post tags</p>
                </div>
                
                <?php if ($flash): ?>
                    <div class="bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-100 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-400 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-700 px-4 py-3 rounded mb-6">
                        <?php echo escape($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Create Tag Form -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Add New Tag</h2>
                    <form method="POST" action="" class="flex items-end gap-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="flex-1">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Tag Name *</label>
                            <input type="text" id="name" name="name" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                   placeholder="e.g., JavaScript, React, Node.js">
                        </div>
                        
                        <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                            Add Tag
                        </button>
                    </form>
                    <p class="text-sm text-gray-500 mt-2">Slug will be automatically generated from the tag name</p>
                </div>
                
                <!-- Tags List -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-800">All Tags</h2>
                    </div>
                    
                    <?php if (empty($tags)): ?>
                        <div class="p-8 text-center text-gray-500">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            <p class="text-lg mb-2">No tags yet</p>
                            <p class="text-sm">Create your first tag using the form above.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($tags as $tag): ?>
                                        <tr class="hover:bg-gray-50" id="tag-<?php echo $tag['id']; ?>">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                                        <?php echo escape($tag['name']); ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500"><?php echo escape($tag['slug']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500"><?php echo formatDate($tag['created_at'], 'M j, Y'); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button onclick="editTag(<?php echo $tag['id']; ?>, '<?php echo escape(addslashes($tag['name'])); ?>')"
                                                        class="text-purple-600 hover:text-purple-900 mr-4">
                                                    Edit
                                                </button>
                                                <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this tag?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Edit Tag</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="mb-4">
                    <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-2">Tag Name *</label>
                    <input type="text" id="edit_name" name="name" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                        Update Tag
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editTag(id, name) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>
