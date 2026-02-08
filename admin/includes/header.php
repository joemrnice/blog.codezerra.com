<header class="bg-white shadow-sm">
    <div class="flex items-center justify-between px-8 py-4">
        <div class="flex items-center">
            <h2 class="text-xl font-bold text-gray-800">CodeZerra Blog Admin</h2>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600">Welcome, <?php echo escape($_SESSION['username']); ?></span>
            <a href="/" class="text-sm text-gray-600 hover:text-gray-800" target="_blank">View Site</a>
            <a href="/admin/logout.php" class="text-sm text-red-600 hover:text-red-800">Logout</a>
        </div>
    </div>
</header>
