<?php
ob_start();
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$page = $_GET['page'] ?? 'local_order';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Pearlz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Amsterdam One';
            font-style: normal;
            font-weight: 400;
            src: local('Amsterdam One'), local('Amsterdam'), url('https://fonts.cdnfonts.com/s/17992/Amsterdam.woff') format('woff');
        }
        .font-logo { font-family: 'Amsterdam One', cursive; font-weight: normal; }
        body { font-family: 'DM Sans', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 flex h-screen overflow-hidden">

    <!-- Mobile Overlay -->
    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-30 hidden opacity-0 transition-opacity duration-300 md:hidden"></div>

    <!-- Sidebar / Nav Partial -->
    <?php include 'partials/nav.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-y-auto">
        <!-- Top header -->
        <header class="bg-white shadow-sm py-4 px-4 md:px-8 flex justify-between items-center z-10 sticky top-0">
            <div class="flex items-center space-x-4">
                <button onclick="toggleSidebar()" class="<?php echo ($page === 'local_order') ? '' : 'md:hidden '; ?>text-gray-600 hover:text-pink-500 focus:outline-none transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <h1 class="text-xl md:text-2xl font-bold text-gray-800 capitalize"><?php echo str_replace('_', ' ', htmlspecialchars($page)); ?></h1>
            </div>
            <div class="flex items-center space-x-4 md:space-x-6">
                <span class="text-sm font-medium text-gray-500 uppercase tracking-widest">Welcome, <strong class="text-pink-600"><?php echo htmlspecialchars($_SESSION['first_name']); ?></strong></span>
                <a href="#" onclick="openLogoutModal()" class="text-xs font-bold uppercase tracking-widest bg-gray-100 text-gray-600 px-4 py-2 rounded-full hover:bg-pink-50 hover:text-pink-600 transition-colors">Logout</a>
            </div>
        </header>

        <!-- Content Area -->
        <div class="p-6 flex-1">
            <?php
            $partial_path = "partials/{$page}.php";
            if (file_exists($partial_path)) {
                include $partial_path;
            } else {
            ?>
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 min-h-full">
                    <h2 class="text-xl font-bold mb-4 text-gray-800 capitalize"><?php echo str_replace('_', ' ', htmlspecialchars($page)); ?> Dashboard</h2>
                    <p class="text-gray-500">This module is currently under construction. Please check back later for updates to the <?php echo str_replace('_', ' ', htmlspecialchars($page)); ?> system.</p>
                </div>
            <?php } ?>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (window.innerWidth < 768) {
                sidebar.classList.toggle('-translate-x-full');
                
                if (sidebar.classList.contains('-translate-x-full')) {
                    overlay.classList.add('opacity-0');
                    setTimeout(() => {
                        overlay.classList.add('hidden');
                    }, 300);
                } else {
                    overlay.classList.remove('hidden');
                    setTimeout(() => {
                        overlay.classList.remove('opacity-0');
                    }, 10);
                }
            } else {
                if (sidebar.style.display === 'none') {
                    sidebar.style.display = '';
                } else {
                    sidebar.style.display = 'none';
                }
            }
        }

        // Auto-close sidebar on POS page for maximum screen space
        <?php if($page === 'local_order'): ?>
        if (window.innerWidth >= 768) {
            document.getElementById('adminSidebar').style.display = 'none';
        }
        <?php endif; ?>
    </script>
    <!-- Logout Modal -->
    <div id="logoutModal" class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white/90 backdrop-blur-md p-8 rounded-3xl shadow-2xl w-full max-w-sm transform scale-95 transition-transform duration-300 mx-4" id="logoutModalContent">
            <h2 class="text-2xl font-bold text-gray-900 mb-4 text-center">Confirm Logout</h2>
            <p class="text-gray-600 text-center mb-8">Are you sure you want to log out?</p>
            <div class="flex justify-center space-x-4">
                <button onclick="closeLogoutModal()" class="px-6 py-2.5 rounded-full text-sm font-bold uppercase tracking-widest text-gray-600 hover:bg-gray-100 transition-colors">Cancel</button>
                <a href="../logout.php" class="px-6 py-2.5 rounded-full text-sm font-bold uppercase tracking-widest bg-red-500 text-white hover:bg-red-600 transition-colors shadow-lg">Log Out</a>
            </div>
        </div>
    </div>

    <script>
        const logoutModal = document.getElementById('logoutModal');
        const logoutModalContent = document.getElementById('logoutModalContent');

        function openLogoutModal() {
            if(logoutModal) {
                logoutModal.classList.remove('hidden');
                setTimeout(() => {
                    logoutModal.classList.remove('opacity-0');
                    logoutModalContent.classList.remove('scale-95');
                    logoutModalContent.classList.add('scale-100');
                }, 10);
            }
        }

        function closeLogoutModal() {
            if(logoutModal) {
                logoutModal.classList.add('opacity-0');
                logoutModalContent.classList.remove('scale-100');
                logoutModalContent.classList.add('scale-95');
                setTimeout(() => {
                    logoutModal.classList.add('hidden');
                }, 300);
            }
        }

        if(logoutModal) {
            logoutModal.addEventListener('click', (e) => {
                if (e.target === logoutModal) closeLogoutModal();
            });
        }
    </script>
</body>
</html>
<?php
ob_end_flush();
?>
