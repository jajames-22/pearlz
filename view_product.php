<?php
// view_product.php
session_start();
require_once 'databse/database.php';
require_once 'models/Product.php';
require_once 'models/User.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$productModel = new Product($db);

$product = $productModel->getById($_GET['id']);
if (!$product) {
    header("Location: index.php");
    exit();
}

$variants = $productModel->getVariants($product['product_id']);
$baseImages = $productModel->getBaseImages($product['product_id']);

$is_logged_in = isset($_SESSION['user_id']);

$login_error = '';
$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            $login_result = $userModel->login($email, $password);
            if ($login_result['status']) {
                $user = $login_result['user'];
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    header("Location: admin/layout.php");
                } else {
                    header("Location: view_product.php?id=" . $product['product_id']);
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


// Determine main image
$mainImage = null;
if (!empty($baseImages)) {
    foreach ($baseImages as $img) {
        if ($img['is_primary']) {
            $mainImage = $img['image_url'];
            break;
        }
    }
    if (!$mainImage) $mainImage = $baseImages[0]['image_url'];
}

if ($is_logged_in && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $variant_id = !empty($_POST['variant_id']) ? $_POST['variant_id'] : null;
    $qty = (int)($_POST['qty'] ?? 1);
    $custom_text = trim($_POST['custom_text'] ?? '');
    
    if ($variant_id) {
        $selectedVariant = null;
        foreach($variants as $v) {
            if ($v['variant_id'] == $variant_id) {
                $selectedVariant = $v;
                break;
            }
        }
        
        if ($selectedVariant) {
            if ($selectedVariant['stock_quantity'] >= $qty) {
                // Check if item already exists in cart
                $stmt = $db->prepare("SELECT cart_id, qty FROM cart WHERE user_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL)) AND custom_text = ?");
                $stmt->execute([$_SESSION['user_id'], $product['product_id'], $variant_id, $variant_id, $custom_text]);
                $existingItem = $stmt->fetch();
                
                if ($existingItem) {
                    $newQty = $existingItem['qty'] + $qty;
                    $updateStmt = $db->prepare("UPDATE cart SET qty = ? WHERE cart_id = ?");
                    $updateStmt->execute([$newQty, $existingItem['cart_id']]);
                } else {
                    $insertStmt = $db->prepare("INSERT INTO cart (user_id, product_id, variant_id, qty, custom_text) VALUES (?, ?, ?, ?, ?)");
                    $insertStmt->execute([$_SESSION['user_id'], $product['product_id'], $variant_id, $qty, $custom_text]);
                }
                
                header("Location: cart.php");
                exit();
            } else {
                $error = "Not enough stock available for this variant.";
            }
        } else {
            $error = "Invalid variant selected.";
        }
    } else {
        $error = "Please select a variant.";
    }
}

// Calculate cart count from database
$cart_count = 0;
if ($is_logged_in) {
    $stmt = $db->prepare("SELECT SUM(qty) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $cart_count = $result['total'] ?: 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> | Pearlz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-pink-50 min-h-screen text-gray-800 antialiased">
    
    <header class="py-4 px-6 md:py-6 md:px-8 flex items-center justify-between bg-white/40 backdrop-blur-md sticky top-0 z-50 border-b border-white/50">
        <div class="flex-1 hidden md:flex space-x-6 text-sm uppercase tracking-widest font-semibold items-center">
            <a href="index.php" class="hover:text-pink-500 transition-colors">Shop</a>
        </div>
        <div class="flex-1 flex justify-start md:justify-center items-center">
            <a href="index.php" class="block">
                <img src="images/logo.png" alt="Logo" class="h-10 md:h-12 object-contain">
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
                        <a href="#" onclick="openLogoutModal()" class="block text-left px-5 py-2.5 text-xs font-bold uppercase tracking-widest text-red-500 hover:bg-red-50 transition-colors">Log Out</a>
                    </div>
                </div>
                <a href="cart.php" class="hover:text-pink-500 transition-colors">Cart (<?php echo $cart_count; ?>)</a>
            <?php else: ?>
                <button id="loginBtn" class="hover:text-pink-500 transition-colors uppercase font-semibold focus:outline-none">Login</button>
                <button type="button" onclick="openModal()" class="hover:text-pink-500 transition-colors uppercase font-semibold focus:outline-none">Cart (<?php echo $cart_count; ?>)</button>
            <?php endif; ?>
        </div>
        
        <div class="flex-1 md:hidden flex justify-end items-center">
            <button id="mobileMenuBtn" class="text-gray-900 focus:outline-none hover:text-pink-500 transition-colors">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-12">
        <div class="flex flex-col md:flex-row gap-12 bg-white/60 backdrop-blur-md p-6 md:p-12 rounded-[3rem] shadow-xl border border-white">
            
            <!-- Image Gallery -->
            <div class="w-full md:w-1/2">
                <div class="rounded-2xl overflow-hidden bg-gray-100 h-72 md:h-96 shadow-inner relative group">
                    <?php if ($mainImage): ?>
                        <img id="mainImageDisplay" src="<?php echo htmlspecialchars($mainImage); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-gray-400">No Image Available</div>
                    <?php endif; ?>
                    
                    <?php if ($product['is_limited_edition']): ?>
                        <span class="absolute top-4 left-4 z-10 bg-gradient-to-r from-blue-600 to-pink-500 text-white text-xs px-4 py-1.5 uppercase tracking-widest rounded-full shadow-lg font-bold">Limited Edition</span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($variants)): ?>
                <div class="flex gap-4 mt-6 overflow-x-auto pb-2">
                    <?php foreach($variants as $v): 
                        if ($v['image_url']): ?>
                        <button onclick="changeMainImage('<?php echo htmlspecialchars($v['image_url']); ?>')" class="w-20 h-20 rounded-xl overflow-hidden border-2 border-transparent hover:border-pink-400 focus:border-pink-500 transition-all flex-shrink-0 bg-gray-50">
                            <img src="<?php echo htmlspecialchars($v['image_url']); ?>" class="w-full h-full object-cover">
                        </button>
                    <?php endif; endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Details -->
            <div class="w-full md:w-1/2 flex flex-col">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="text-2xl font-bold text-pink-600 mb-6" id="priceDisplay">₱<?php echo number_format($product['base_price'], 2); ?></p>
                
                <div class="prose text-gray-600 mb-10 leading-relaxed text-sm md:text-base">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-500 border border-red-200 p-4 rounded-xl mb-6 text-sm font-bold">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="view_product.php?id=<?php echo $product['product_id']; ?>" class="mt-auto space-y-6">
                    <input type="hidden" name="action" value="add_to_cart">
                    
                    <?php if (!empty($variants)): ?>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-3">Select Variant</label>
                            <div class="grid grid-cols-2 gap-3">
                                <?php foreach($variants as $v): 
                                    $price = $v['price_override'] !== null ? $v['price_override'] : $product['base_price'];
                                    $isOutOfStock = $v['stock_quantity'] <= 0;
                                ?>
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="variant_id" value="<?php echo $v['variant_id']; ?>" 
                                           class="peer sr-only" 
                                           <?php echo $isOutOfStock ? 'disabled' : 'required'; ?>
                                           data-price="<?php echo $price; ?>"
                                           onchange="updatePrice(this)">
                                    <div class="p-4 rounded-2xl border-2 <?php echo $isOutOfStock ? 'bg-gray-50 border-gray-100 opacity-50 cursor-not-allowed' : 'bg-white border-gray-200 peer-checked:border-pink-500 peer-checked:ring-1 peer-checked:ring-pink-500 hover:border-pink-300'; ?> transition-all text-center">
                                        <div class="font-bold text-gray-900 text-sm mb-1"><?php echo htmlspecialchars($v['variant_name']); ?></div>
                                        <?php if (!$isOutOfStock): ?>
                                            <div class="text-xs text-gray-500">₱<?php echo number_format($price, 2); ?></div>
                                        <?php else: ?>
                                            <div class="text-xs text-red-500 font-bold">Out of Stock</div>
                                        <?php endif; ?>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-yellow-50 text-yellow-700 p-4 rounded-xl text-sm font-bold">
                            This product is currently unavailable (no variants configured).
                        </div>
                    <?php endif; ?>

                    <?php if ($product['allows_custom_text']): ?>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Custom Engraving (Optional)</label>
                        <input type="text" name="custom_text" maxlength="<?php echo $product['custom_text_limit'] ?: 20; ?>" 
                               placeholder="Enter your custom text..." 
                               class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-500 focus:ring-1 focus:ring-pink-500 transition-all">
                        <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-widest">Max <?php echo $product['custom_text_limit'] ?: 20; ?> characters.</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex space-x-4 items-end">
                        <div class="w-24">
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Qty</label>
                            <input type="number" name="qty" value="1" min="1" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-center text-lg font-bold focus:outline-none focus:border-pink-500 focus:ring-1 focus:ring-pink-500 transition-all">
                        </div>
                        <?php if ($is_logged_in): ?>
                            <button type="submit" <?php echo empty($variants) ? 'disabled' : ''; ?> class="flex-1 bg-gradient-to-r from-blue-600 to-pink-500 text-white rounded-xl py-4 font-bold uppercase tracking-widest text-sm hover:opacity-90 transition-opacity shadow-xl shadow-pink-500/30 disabled:opacity-50 disabled:cursor-not-allowed">
                                Add to Cart
                            </button>
                        <?php else: ?>
                            <button type="button" onclick="openModal()" class="flex-1 bg-gradient-to-r from-blue-600 to-pink-500 text-white rounded-xl py-4 font-bold uppercase tracking-widest text-sm hover:opacity-90 transition-opacity shadow-xl shadow-pink-500/30">
                                Add to Cart
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <!-- Login Modal -->
    <div id="loginModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white/90 backdrop-blur-md p-8 rounded-3xl shadow-2xl w-full max-w-md transform scale-95 transition-transform duration-300 mx-4" id="loginModalContent">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Welcome Back</h2>
                <button id="closeLoginBtn" class="text-gray-500 hover:text-pink-500 focus:outline-none transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="POST" action="">
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
            <a href="index.php" class="text-lg font-semibold uppercase tracking-widest hover:text-pink-500 transition-colors">Shop</a>
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
                        <a href="#" onclick="openLogoutModal()" class="text-sm font-semibold uppercase tracking-widest text-red-500 hover:text-red-700 transition-colors pl-9">Log Out</a>
                    </div>
                    <a href="cart.php" class="text-lg font-semibold uppercase tracking-widest hover:text-pink-500 transition-colors pt-2 border-t border-gray-100">Cart (<?php echo $cart_count; ?>)</a>
                <?php else: ?>
                    <button id="mobileLoginBtn" class="text-left text-lg font-semibold uppercase tracking-widest hover:text-pink-500 transition-colors focus:outline-none">Login</button>
                    <button onclick="closeDrawer(); openModal();" class="text-left text-lg font-semibold uppercase tracking-widest hover:text-pink-500 transition-colors focus:outline-none">Cart (<?php echo $cart_count; ?>)</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Drawer Overlay -->
    <div id="drawerOverlay" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[50] hidden opacity-0 transition-opacity duration-300"></div>

    <script>
        function updatePrice(radio) {
            const price = parseFloat(radio.getAttribute('data-price'));
            if (!isNaN(price)) {
                document.getElementById('priceDisplay').textContent = '₱' + price.toFixed(2);
            }
        }

        function changeMainImage(url) {
            const mainImg = document.getElementById('mainImageDisplay');
            if (mainImg) {
                mainImg.src = url;
            }
        }

        // Modal Logic
        const loginModal = document.getElementById('loginModal');
        const loginModalContent = document.getElementById('loginModalContent');
        const loginBtn = document.getElementById('loginBtn');
        const mobileLoginBtn = document.getElementById('mobileLoginBtn');
        const closeLoginBtn = document.getElementById('closeLoginBtn');

        function openModal() {
            if(loginModal) {
                loginModal.classList.remove('hidden');
                setTimeout(() => {
                    loginModal.classList.remove('opacity-0');
                    loginModalContent.classList.remove('scale-95');
                    loginModalContent.classList.add('scale-100');
                }, 10);
            }
        }

        function closeModal() {
            if(loginModal) {
                loginModal.classList.add('opacity-0');
                loginModalContent.classList.remove('scale-100');
                loginModalContent.classList.add('scale-95');
                setTimeout(() => {
                    loginModal.classList.add('hidden');
                }, 300);
            }
        }

        if(loginBtn) loginBtn.addEventListener('click', openModal);
        if(mobileLoginBtn) mobileLoginBtn.addEventListener('click', () => {
            closeDrawer();
            openModal();
        });
        if(closeLoginBtn) closeLoginBtn.addEventListener('click', closeModal);
        if(loginModal) {
            loginModal.addEventListener('click', (e) => {
                if (e.target === loginModal) closeModal();
            });
        }

        // Drawer Logic
        const navDrawer = document.getElementById('navDrawer');
        const drawerOverlay = document.getElementById('drawerOverlay');
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const closeMenuBtn = document.getElementById('closeMenuBtn');

        function openDrawer() {
            if(drawerOverlay) {
                drawerOverlay.classList.remove('hidden');
                setTimeout(() => {
                    drawerOverlay.classList.remove('opacity-0');
                    navDrawer.classList.remove('translate-x-full');
                }, 10);
            }
        }

        function closeDrawer() {
            if(drawerOverlay) {
                drawerOverlay.classList.add('opacity-0');
                navDrawer.classList.add('translate-x-full');
                setTimeout(() => {
                    drawerOverlay.classList.add('hidden');
                }, 300);
            }
        }

        if(mobileMenuBtn) mobileMenuBtn.addEventListener('click', openDrawer);
        if(closeMenuBtn) closeMenuBtn.addEventListener('click', closeDrawer);
        if(drawerOverlay) drawerOverlay.addEventListener('click', closeDrawer);

        <?php if ($login_error): ?>
            openModal();
        <?php endif; ?>
    </script>
    <!-- Logout Modal -->
    <div id="logoutModal" class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white/90 backdrop-blur-md p-8 rounded-3xl shadow-2xl w-full max-w-sm transform scale-95 transition-transform duration-300 mx-4" id="logoutModalContent">
            <h2 class="text-2xl font-bold text-gray-900 mb-4 text-center">Confirm Logout</h2>
            <p class="text-gray-600 text-center mb-8">Are you sure you want to log out?</p>
            <div class="flex justify-center space-x-4">
                <button onclick="closeLogoutModal()" class="px-6 py-2.5 rounded-full text-sm font-bold uppercase tracking-widest text-gray-600 hover:bg-gray-100 transition-colors">Cancel</button>
                <a href="logout.php" class="px-6 py-2.5 rounded-full text-sm font-bold uppercase tracking-widest bg-red-500 text-white hover:bg-red-600 transition-colors shadow-lg">Log Out</a>
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
