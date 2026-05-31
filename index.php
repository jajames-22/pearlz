<?php
// index.php
session_start();
require_once 'databse/database.php';
require_once 'models/User.php';

$database = new Database();
$db = $database->getConnection(); 
$userModel = new User($db);

$login_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            $login_result = $userModel->login($email, $password);

            if ($login_result['status']) {
                $user = $login_result['user'];
                // Password is correct, start session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    header("Location: admin/layout.php");
                } else {
                    // Redirect to prevent form resubmission
                    header("Location: index.php");
                }
                exit();
            } else {
                $login_error = $login_result['message'];
            }
        } catch(PDOException $e) {
            $login_error = "Database error during login.";
        }
    } else {
        $login_error = "Please fill in all fields.";
    }
}

$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pearlz | Exclusive Customized Elegance</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Amsterdam One';
            font-style: normal;
            font-weight: 400;
            src: local('Amsterdam One'), local('Amsterdam'), url('https://fonts.cdnfonts.com/s/17992/Amsterdam.woff') format('woff');
        }
        /* Custom font utility for the logo and headings */
        .font-logo { font-family: 'Amsterdam One', cursive; font-weight: normal; }
        body { font-family: 'DM Sans', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-100 via-blue-50 to-pink-100 min-h-screen text-gray-800 antialiased">

    <header class="py-4 px-6 md:py-6 md:px-8 flex items-center justify-between bg-white/40 backdrop-blur-md sticky top-0 z-50 border-b border-white/50">
        
        <div class="flex-1 hidden md:flex space-x-6 text-sm uppercase tracking-widest font-semibold items-center">
            <a href="#" class="hover:text-pink-500 transition-colors">Shop</a>
            <a href="#" class="hover:text-pink-500 transition-colors">About</a>
        </div>

        <div class="flex-1 flex justify-start md:justify-center items-center">
            <a href="index.php" class="block">
                <img src="images/logo.png" alt="Logo Placeholder" class="h-10 md:h-12 object-contain">
            </a>
        </div>

        <div class="flex-1 hidden md:flex justify-end space-x-6 text-sm uppercase tracking-widest font-semibold items-center">
            <?php if ($is_logged_in): ?>
                <div class="relative group cursor-pointer py-4">
                    <div class="hover:text-pink-500 transition-colors flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span><?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                    </div>
                    
                    <div class="absolute right-0 top-full mt-0 w-56 bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl py-3 hidden group-hover:block transition-all border border-gray-100 opacity-0 group-hover:opacity-100">
                        <div class="px-5 py-3 border-b border-gray-100/60 mb-2 text-left">
                            <p class="text-[10px] text-gray-500 uppercase tracking-widest font-bold mb-1">Signed in as</p>
                            <p class="font-bold text-gray-900 truncate text-sm"><?php echo htmlspecialchars($_SESSION['first_name']); ?></p>
                        </div>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="admin/layout.php" class="block text-left px-5 py-2.5 text-xs font-bold uppercase tracking-widest text-gray-600 hover:bg-pink-50 hover:text-pink-600 transition-colors">Dashboard</a>
                        <?php endif; ?>
                        <a href="logout.php" class="block text-left px-5 py-2.5 text-xs font-bold uppercase tracking-widest text-red-500 hover:bg-red-50 transition-colors">Log Out</a>
                    </div>
                </div>
                <a href="cart.php" class="hover:text-pink-500 transition-colors">Cart (0)</a>
            <?php else: ?>
                <button id="loginBtn" class="hover:text-pink-500 transition-colors uppercase font-semibold focus:outline-none">Login</button>
                <button onclick="openModal()" class="hover:text-pink-500 transition-colors uppercase font-semibold focus:outline-none">Cart (0)</button>
            <?php endif; ?>
        </div>
        
        <div class="flex-1 md:hidden flex justify-end items-center">
            <button id="mobileMenuBtn" class="text-gray-900 focus:outline-none hover:text-pink-500 transition-colors">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        
        <section class="text-center py-24">
            <h1 class="text-5xl md:text-7xl font-bold mb-6 text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-pink-500">Handmade warmth, pearlescent charm.</h1>
            <p class="text-lg md:text-xl max-w-2xl mx-auto mb-10 text-gray-700 leading-relaxed">
                Discover our exclusive collections of limited-edition jewelry. Select your variant and make it yours with personalized engravings.
            </p>
            <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                <a href="#featured" class="inline-block bg-gray-900 text-white px-10 py-4 uppercase tracking-widest text-sm hover:bg-gray-800 transition shadow-xl rounded-full">
                    Shop the Collection
                </a>
                <div class="relative">
                    <input type="text" placeholder="Search..." class="bg-white/50 border border-gray-300 rounded-full px-6 py-4 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 w-64 lg:w-80 transition-all shadow-xl">
                    <svg class="w-5 h-5 text-gray-500 absolute right-4 top-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
        </section>

        <section id="featured" class="">
            <div class="flex justify-between items-end mb-10">
                <h2 class="text-3xl font-bold text-gray-900 mt-28">Featured Exclusives</h2>
                <a href="#" class="text-sm font-semibold uppercase tracking-wider text-pink-600 hover:text-pink-800 transition">View All &rarr;</a>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-10">
                
                <div class="group cursor-pointer">
                    <div class="h-80 bg-white/60 backdrop-blur-sm rounded-3xl mb-4 flex items-center justify-center text-gray-400 overflow-hidden shadow-sm group-hover:shadow-md transition duration-300">
                        <span class="text-sm uppercase tracking-widest">[Feature Image]</span>
                    </div>
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-lg text-gray-900 group-hover:text-pink-600 transition">Signature Cuff</h3>
                            <p class="text-gray-500 text-sm mt-1">Custom Engraving Available</p>
                        </div>
                        <p class="font-semibold text-gray-900">₱250.00</p>
                    </div>
                </div>

                <div class="group cursor-pointer">
                    <div class="h-80 bg-white/60 backdrop-blur-sm rounded-3xl mb-4 flex items-center justify-center text-gray-400 overflow-hidden shadow-sm group-hover:shadow-md transition duration-300 relative">
                        <span class="absolute top-4 left-4 bg-gradient-to-r from-blue-600 to-pink-500 text-white text-xs px-3 py-1 uppercase tracking-wider rounded-full">Limited</span>
                        <span class="text-sm uppercase tracking-widest">[Feature Image]</span>
                    </div>
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-lg text-gray-900 group-hover:text-pink-600 transition">Rose Gold Timepiece</h3>
                            <p class="text-gray-500 text-sm mt-1">3 Variants</p>
                        </div>
                        <p class="font-semibold text-gray-900">₱1,200.00</p>
                    </div>
                </div>

                <div class="group cursor-pointer">
                    <div class="h-80 bg-white/60 backdrop-blur-sm rounded-3xl mb-4 flex items-center justify-center text-gray-400 overflow-hidden shadow-sm group-hover:shadow-md transition duration-300">
                        <span class="text-sm uppercase tracking-widest">[Feature Image]</span>
                    </div>
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-lg text-gray-900 group-hover:text-pink-600 transition">Pearl Drop Earrings</h3>
                            <p class="text-gray-500 text-sm mt-1">Silver / Gold</p>
                        </div>
                        <p class="font-semibold text-gray-900">$185.00</p>
                    </div>
                </div>

            </div>
        </section>
    </main>

    <footer class="bg-white/30 backdrop-blur-md border-t border-white/50 mt-24 py-10">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-500 text-sm">&copy; <?php echo date("Y"); ?> Pearlz Exclusive. All rights reserved.</p>
        </div>
    </footer>

    <!-- Login Modal -->
    <div id="loginModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white/90 backdrop-blur-md p-8 rounded-3xl shadow-2xl w-full max-w-md transform scale-95 transition-transform duration-300 mx-4" id="loginModalContent">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Welcome Back</h2>
                <button id="closeLoginBtn" class="text-gray-500 hover:text-pink-500 focus:outline-none transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="POST" action="index.php">
                <input type="hidden" name="action" value="login">
                <?php if ($login_error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 mb-4 text-sm rounded" role="alert">
                        <p><?php echo htmlspecialchars($login_error); ?></p>
                    </div>
                <?php endif; ?>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="w-full bg-white/50 border border-gray-300 rounded-full px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all" placeholder="you@example.com" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="password">Password</label>
                    <input type="password" id="password" name="password" class="w-full bg-white/50 border border-gray-300 rounded-full px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required>
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-pink-500 text-white rounded-full py-3 font-semibold uppercase tracking-widest text-sm hover:opacity-90 transition-opacity shadow-lg">Sign In</button>
            </form>
            <div class="mt-6 text-center flex flex-col space-y-3">
                <a href="#" class="text-sm text-pink-600 hover:text-pink-800 transition-colors mb-2">Forgot your password?</a>
                <div class="pt-4 border-t border-gray-100">
                    <p class="text-sm text-gray-500 mb-3">Don't have an account?</p>
                    <a href="register.php" class="block w-full bg-pink-50 text-pink-600 border border-pink-200 rounded-full py-3 font-semibold uppercase tracking-widest text-sm hover:bg-pink-100 transition-colors shadow-sm">Create New Account</a>
                    <p class="text-[11px] text-pink-500 mt-2 font-medium italic">Promise! this wont take you a minute.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Nav Drawer -->
    <div id="navDrawer" class="fixed inset-y-0 right-0 z-[60] w-72 bg-white/90 backdrop-blur-xl shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col">
        <div class="p-6 flex justify-end">
            <button id="closeMenuBtn" class="text-gray-900 focus:outline-none hover:text-pink-500 transition-colors">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="px-6 pb-6 flex flex-col space-y-6 flex-1">
            <div class="relative">
                <input type="text" placeholder="Search..." class="w-full bg-gray-100/50 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all">
                <svg class="w-5 h-5 text-gray-500 absolute right-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <a href="#" class="text-lg font-semibold uppercase tracking-widest hover:text-pink-500 transition-colors">Shop</a>
            <a href="#" class="text-lg font-semibold uppercase tracking-widest hover:text-pink-500 transition-colors">About</a>
            <div class="border-t border-gray-200 pt-6 flex flex-col space-y-6">
                <?php if ($is_logged_in): ?>
                    <div class="flex flex-col space-y-4">
                        <div class="text-lg font-semibold uppercase tracking-widest text-pink-500 flex items-center space-x-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <span><?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                        </div>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="admin/layout.php" class="text-sm font-semibold uppercase tracking-widest text-gray-600 hover:text-pink-500 transition-colors pl-9">Dashboard</a>
                        <?php endif; ?>
                        <a href="logout.php" class="text-sm font-semibold uppercase tracking-widest text-red-500 hover:text-red-700 transition-colors pl-9">Log Out</a>
                    </div>
                    <a href="cart.php" class="text-lg font-semibold uppercase tracking-widest hover:text-pink-500 transition-colors pt-2 border-t border-gray-100">Cart (0)</a>
                <?php else: ?>
                    <button id="mobileLoginBtn" class="text-left text-lg font-semibold uppercase tracking-widest hover:text-pink-500 transition-colors focus:outline-none">Login</button>
                    <button onclick="closeDrawer(); openModal();" class="text-left text-lg font-semibold uppercase tracking-widest hover:text-pink-500 transition-colors focus:outline-none">Cart (0)</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Drawer Overlay -->
    <div id="drawerOverlay" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[50] hidden opacity-0 transition-opacity duration-300"></div>

    <script>
        // Modal Logic
        const loginModal = document.getElementById('loginModal');
        const loginModalContent = document.getElementById('loginModalContent');
        const loginBtn = document.getElementById('loginBtn');
        const mobileLoginBtn = document.getElementById('mobileLoginBtn');
        const closeLoginBtn = document.getElementById('closeLoginBtn');

        function openModal() {
            loginModal.classList.remove('hidden');
            setTimeout(() => {
                loginModal.classList.remove('opacity-0');
                loginModalContent.classList.remove('scale-95');
                loginModalContent.classList.add('scale-100');
            }, 10);
        }

        function closeModal() {
            loginModal.classList.add('opacity-0');
            loginModalContent.classList.remove('scale-100');
            loginModalContent.classList.add('scale-95');
            setTimeout(() => {
                loginModal.classList.add('hidden');
            }, 300);
        }

        if(loginBtn) loginBtn.addEventListener('click', openModal);
        if(mobileLoginBtn) mobileLoginBtn.addEventListener('click', () => {
            closeDrawer();
            openModal();
        });
        if(closeLoginBtn) closeLoginBtn.addEventListener('click', closeModal);
        loginModal.addEventListener('click', (e) => {
            if (e.target === loginModal) closeModal();
        });

        // Drawer Logic
        const navDrawer = document.getElementById('navDrawer');
        const drawerOverlay = document.getElementById('drawerOverlay');
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const closeMenuBtn = document.getElementById('closeMenuBtn');

        function openDrawer() {
            drawerOverlay.classList.remove('hidden');
            setTimeout(() => {
                drawerOverlay.classList.remove('opacity-0');
                navDrawer.classList.remove('translate-x-full');
            }, 10);
        }

        function closeDrawer() {
            drawerOverlay.classList.add('opacity-0');
            navDrawer.classList.add('translate-x-full');
            setTimeout(() => {
                drawerOverlay.classList.add('hidden');
            }, 300);
        }

        if(mobileMenuBtn) mobileMenuBtn.addEventListener('click', openDrawer);
        if(closeMenuBtn) closeMenuBtn.addEventListener('click', closeDrawer);
        if(drawerOverlay) drawerOverlay.addEventListener('click', closeDrawer);

        <?php if ($login_error): ?>
            // Re-open modal if there was a login error
            openModal();
        <?php endif; ?>
    </script>
</body>
</html>