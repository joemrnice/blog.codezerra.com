<?php
/**
 * Media Library - Admin
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

setSecurityHeaders();
requireLogin();

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', 'Invalid CSRF token');
        redirect('/admin/media/index.php');
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        setFlashMessage('error', 'Invalid media ID');
    } else {
        // Get media file info before deletion
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT file_path FROM media WHERE id = ?");
        $stmt->execute([$id]);
        $media = $stmt->fetch();
        
        if ($media && deleteMedia($id)) {
            // Delete the physical file
            $filePath = __DIR__ . '/../../' . $media['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            setFlashMessage('success', 'Media deleted successfully');
        } else {
            setFlashMessage('error', 'Failed to delete media');
        }
    }
    redirect('/admin/media/index.php');
}

// Get all media
$media = getAllMedia(100, 0);
$flash = getFlashMessage();
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Library - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="flex">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-8">
            <div class="max-w-7xl mx-auto">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Media Library</h1>
                        <p class="text-gray-600 mt-2">Manage uploaded images and files</p>
                    </div>
                    <a href="/admin/media/upload.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition">
                        + Upload Media
                    </a>
                </div>
                
                <?php if ($flash): ?>
                    <div class="bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-100 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-400 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-700 px-4 py-3 rounded mb-6">
                        <?php echo escape($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($media)): ?>
                    <div class="bg-white rounded-lg shadow-md p-12 text-center">
                        <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <h2 class="text-2xl font-semibold text-gray-700 mb-2">No media files yet</h2>
                        <p class="text-gray-500 mb-6">Upload your first image to get started</p>
                        <a href="/admin/media/upload.php" class="inline-block bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition">
                            Upload Media
                        </a>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            <?php foreach ($media as $item): ?>
                                <div class="group relative bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition">
                                    <!-- Image -->
                                    <div class="aspect-square bg-gray-100 flex items-center justify-center overflow-hidden">
                                        <?php if (in_array($item['file_type'], ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'])): ?>
                                            <img src="/<?php echo escape($item['file_path']); ?>" 
                                                 alt="<?php echo escape($item['alt_text'] ?? $item['original_filename']); ?>"
                                                 class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Info -->
                                    <div class="p-4">
                                        <h3 class="text-sm font-medium text-gray-900 truncate mb-1" title="<?php echo escape($item['original_filename']); ?>">
                                            <?php echo escape($item['original_filename']); ?>
                                        </h3>
                                        <div class="flex items-center justify-between text-xs text-gray-500">
                                            <span><?php echo number_format($item['file_size'] / 1024, 1); ?> KB</span>
                                            <span><?php echo formatDate($item['created_at'], 'M j, Y'); ?></span>
                                        </div>
                                        <?php if ($item['alt_text']): ?>
                                            <p class="text-xs text-gray-500 mt-2 truncate" title="<?php echo escape($item['alt_text']); ?>">
                                                Alt: <?php echo escape($item['alt_text']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="text-xs text-gray-400 mt-1">
                                            By <?php echo escape($item['uploaded_by_name']); ?>
                                        </p>
                                    </div>
                                    
                                    <!-- Actions Overlay -->
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition flex items-center justify-center opacity-0 group-hover:opacity-100">
                                        <div class="flex gap-2">
                                            <button onclick="copyUrl('<?php echo escape(SITE_URL . '/' . $item['file_path']); ?>')" 
                                                    class="bg-white text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-100 transition text-sm font-medium">
                                                Copy URL
                                            </button>
                                            <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this media file?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm font-medium">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        function copyUrl(url) {
            navigator.clipboard.writeText(url).then(function() {
                // Show temporary success message
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                notification.textContent = 'URL copied to clipboard!';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }, function() {
                alert('Failed to copy URL');
            });
        }
    </script>
</body>
</html>
