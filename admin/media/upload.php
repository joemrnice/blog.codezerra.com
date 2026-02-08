<?php
/**
 * Media Upload - Admin
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

setSecurityHeaders();
requireLogin();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', 'Invalid CSRF token');
        redirect('/admin/media/upload.php');
    }
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
        setFlashMessage('error', 'Please select a file to upload');
        redirect('/admin/media/upload.php');
    }
    
    // Validate file
    $validation = validateFileUpload($_FILES['file']);
    
    if (!$validation['success']) {
        setFlashMessage('error', $validation['message']);
        redirect('/admin/media/upload.php');
    }
    
    // Create secure filename
    $originalFilename = basename($_FILES['file']['name']);
    $secureFilename = createSecureFilename($originalFilename);
    
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Move uploaded file
    $uploadPath = $uploadDir . $secureFilename;
    $relativePath = 'uploads/' . $secureFilename;
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {
        // Get file info
        $fileType = $_FILES['file']['type'];
        $fileSize = $_FILES['file']['size'];
        $altText = sanitizeInput($_POST['alt_text'] ?? '');
        $uploadedBy = $_SESSION['user_id'];
        
        // Save to database
        if (saveMedia($secureFilename, $originalFilename, $relativePath, $fileType, $fileSize, $uploadedBy, $altText)) {
            setFlashMessage('success', 'File uploaded successfully');
            redirect('/admin/media/index.php');
        } else {
            // Delete the file if database save failed
            if (file_exists($uploadPath)) {
                unlink($uploadPath);
            }
            setFlashMessage('error', 'Failed to save file information to database');
            redirect('/admin/media/upload.php');
        }
    } else {
        setFlashMessage('error', 'Failed to upload file');
        redirect('/admin/media/upload.php');
    }
}

$flash = getFlashMessage();
$csrfToken = generateCSRFToken();
$maxUploadSizeMB = MAX_UPLOAD_SIZE / 1048576; // Convert bytes to MB
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Media - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="flex">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-8">
            <div class="max-w-4xl mx-auto">
                <div class="mb-8">
                    <div class="flex items-center gap-4 mb-2">
                        <a href="/admin/media/index.php" class="text-gray-600 hover:text-gray-800">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </a>
                        <h1 class="text-3xl font-bold text-gray-800">Upload Media</h1>
                    </div>
                    <p class="text-gray-600 ml-10">Upload images and files to your media library</p>
                </div>
                
                <?php if ($flash): ?>
                    <div class="bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-100 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-400 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-700 px-4 py-3 rounded mb-6">
                        <?php echo escape($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="bg-white rounded-lg shadow-md p-8">
                    <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <!-- Drag and Drop Area -->
                        <div id="dropzone" class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center hover:border-purple-500 transition cursor-pointer bg-gray-50">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <div id="dropzoneText">
                                <h3 class="text-xl font-semibold text-gray-700 mb-2">Drop your image here</h3>
                                <p class="text-gray-500 mb-4">or</p>
                                <label for="file" class="inline-block bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition cursor-pointer">
                                    Choose File
                                </label>
                                <input type="file" id="file" name="file" accept="image/*" required class="hidden">
                                <p class="text-sm text-gray-400 mt-4">
                                    Supported formats: JPG, PNG, GIF, WebP<br>
                                    Maximum file size: <?php echo $maxUploadSizeMB; ?> MB
                                </p>
                            </div>
                        </div>
                        
                        <!-- Preview Area -->
                        <div id="preview" class="mt-6 hidden">
                            <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                                <img id="previewImage" src="" alt="Preview" class="w-32 h-32 object-cover rounded">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-800 mb-1" id="fileName"></h4>
                                    <p class="text-sm text-gray-500" id="fileSize"></p>
                                    <button type="button" onclick="clearFile()" class="mt-2 text-sm text-red-600 hover:text-red-800">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Alt Text Field -->
                        <div class="mt-6">
                            <label for="alt_text" class="block text-sm font-medium text-gray-700 mb-2">
                                Alt Text (Optional)
                            </label>
                            <input type="text" id="alt_text" name="alt_text" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                   placeholder="Brief description of the image for accessibility">
                            <p class="text-sm text-gray-500 mt-1">
                                Describe the image for screen readers and SEO
                            </p>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="flex gap-3 mt-8">
                            <button type="submit" id="submitBtn" 
                                    class="bg-purple-600 text-white px-8 py-3 rounded-lg hover:bg-purple-700 transition font-medium">
                                Upload File
                            </button>
                            <a href="/admin/media/index.php" 
                               class="px-8 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition font-medium">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('file');
        const preview = document.getElementById('preview');
        const previewImage = document.getElementById('previewImage');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const dropzoneText = document.getElementById('dropzoneText');
        const submitBtn = document.getElementById('submitBtn');
        
        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Highlight drop zone when dragging over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => {
                dropzone.classList.add('border-purple-500', 'bg-purple-50');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => {
                dropzone.classList.remove('border-purple-500', 'bg-purple-50');
            }, false);
        });
        
        // Handle dropped files
        dropzone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFiles(files);
            }
        }, false);
        
        // Handle file input change
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFiles(e.target.files);
            }
        });
        
        function handleFiles(files) {
            const file = files[0];
            
            // Validate file type
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                return;
            }
            
            // Validate file size
            const maxSize = <?php echo MAX_UPLOAD_SIZE; ?>;
            if (file.size > maxSize) {
                alert('File size exceeds maximum allowed (<?php echo $maxUploadSizeMB; ?> MB)');
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImage.src = e.target.result;
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                preview.classList.remove('hidden');
                dropzoneText.classList.add('hidden');
                submitBtn.disabled = false;
            };
            reader.readAsDataURL(file);
        }
        
        function clearFile() {
            fileInput.value = '';
            preview.classList.add('hidden');
            dropzoneText.classList.remove('hidden');
            submitBtn.disabled = true;
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
        
        // Disable submit button initially if no file
        if (!fileInput.files.length) {
            submitBtn.disabled = true;
        }
        
        // Show loading state on submit
        document.getElementById('uploadForm').addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="inline-block animate-spin mr-2">⏳</span> Uploading...';
        });
    </script>
</body>
</html>
