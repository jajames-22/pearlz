<?php
// cart.php
session_start();

$is_logged_in = isset($_SESSION['user_id']);

if (!$is_logged_in) {
    header("Location: index.php");
    exit();
}

require_once 'databse/database.php';
$database = new Database();
$db = $database->getConnection();

// Handle cart updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'remove') {
        $cart_id = (int)$_POST['cart_id'];
        $stmt = $db->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $_SESSION['user_id']]);
    } elseif ($action === 'update_qty') {
        $cart_id = (int)$_POST['cart_id'];
        $qty = (int)$_POST['qty'];
        if ($qty > 0) {
            $stmt = $db->prepare("UPDATE cart SET qty = ? WHERE cart_id = ? AND user_id = ?");
            $stmt->execute([$qty, $cart_id, $_SESSION['user_id']]);
        } else {
            $stmt = $db->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $_SESSION['user_id']]);
        }
    } elseif ($action === 'apply_voucher') {
        $_SESSION['voucher_code'] = trim($_POST['voucher_code'] ?? '');
    } elseif ($action === 'remove_voucher') {
        unset($_SESSION['voucher_code']);
    }
    
    header("Location: cart.php");
    exit();
}

// Fetch cart items
$stmt = $db->prepare("
    SELECT c.cart_id, c.qty, c.custom_text, p.name, p.base_price, 
           v.variant_name, v.price_override, (SELECT image_url FROM product_images WHERE variant_id = v.variant_id LIMIT 1) as variant_image,
           (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    LEFT JOIN product_variants v ON c.variant_id = v.variant_id
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

// Calculate totals
$subtotal = 0;
$cart_count = 0;
foreach ($cart_items as &$item) {
    $price = $item['price_override'] !== null ? $item['price_override'] : $item['base_price'];
    $item['price'] = $price;
    $item['image'] = $item['variant_image'] ?: $item['primary_image'];
    $subtotal += $price * $item['qty'];
    $cart_count += $item['qty'];
}
$total = $subtotal;
$discount = 0;
$voucher_applied = false;
$voucher_message = '';

if (isset($_SESSION['voucher_code'])) {
    $stmt = $db->prepare("SELECT * FROM vouchers WHERE code = ? AND is_active = 1");
    $stmt->execute([$_SESSION['voucher_code']]);
    $voucher = $stmt->fetch();
    
    if ($voucher) {
        if ($voucher['max_uses'] !== null && $voucher['used_count'] >= $voucher['max_uses']) {
            $voucher_message = "Voucher usage limit reached.";
            unset($_SESSION['voucher_code']);
        } elseif ($subtotal >= $voucher['min_spend']) {
            if ($voucher['discount_type'] === 'percentage') {
                $discount = $subtotal * ($voucher['discount_value'] / 100);
            } else {
                $discount = $voucher['discount_value'];
            }
            if ($discount > $subtotal) {
                $discount = $subtotal;
            }
            $total = $subtotal - $discount;
            $voucher_applied = true;
            $voucher_message = "Voucher applied successfully!";
        } else {
            $voucher_message = "Minimum spend of ₱" . number_format($voucher['min_spend'], 2) . " required.";
            unset($_SESSION['voucher_code']);
        }
    } else {
        $voucher_message = "Invalid or expired voucher code.";
        unset($_SESSION['voucher_code']);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart | Pearlz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-pink-50 min-h-screen text-gray-800 antialiased flex flex-col">
    
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
                <span class="text-pink-500">Cart (<?php echo $cart_count; ?>)</span>
                <a href="#" onclick="openLogoutModal()" class="text-red-500 hover:text-red-600 transition-colors">Log Out</a>
            <?php else: ?>
                <a href="index.php" class="hover:text-pink-500 transition-colors uppercase font-semibold">Login</a>
                <span class="text-pink-500 uppercase font-semibold">Cart (<?php echo $cart_count; ?>)</span>
            <?php endif; ?>
        </div>
    </header>

    <main class="flex-1 max-w-6xl mx-auto px-4 py-12 md:py-16 w-full">
        
        <?php if (isset($_GET['success']) && $_GET['success'] === 'order_placed'): ?>
            <div class="max-w-2xl mx-auto bg-green-50 border border-green-200 text-green-700 p-8 rounded-[2rem] text-center mb-12 shadow-sm">
                <svg class="w-16 h-16 mx-auto mb-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h2 class="text-2xl font-bold mb-2">Order Placed Successfully!</h2>
                <p class="text-green-600">Thank you for shopping with Pearlz. Your custom jewelry is being prepared.</p>
                <a href="index.php" class="inline-block mt-6 bg-green-600 text-white px-8 py-3 rounded-xl text-sm font-bold uppercase tracking-widest hover:bg-green-700 transition">Continue Shopping</a>
            </div>
        <?php endif; ?>

        <h1 class="text-4xl font-bold text-gray-900 mb-10">Your Cart</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="bg-white/60 backdrop-blur-md p-12 rounded-[3rem] shadow-sm border border-white text-center">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-400">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Your cart is empty</h2>
                <p class="text-gray-500 mb-8">Looks like you haven't added any exclusive items to your cart yet.</p>
                <a href="index.php" class="bg-gradient-to-r from-blue-600 to-pink-500 text-white px-10 py-4 rounded-full text-sm font-bold uppercase tracking-widest hover:opacity-90 transition-opacity shadow-lg">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Cart Items -->
                <div class="w-full lg:w-2/3 space-y-4">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="bg-white/80 backdrop-blur-md p-4 md:p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col sm:flex-row items-start sm:items-center gap-6 relative">
                            <div class="w-24 h-24 rounded-2xl bg-gray-50 overflow-hidden flex-shrink-0">
                                <?php if ($item['image']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-300">N/A</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex-1">
                                <h3 class="font-bold text-lg text-gray-900"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <?php if ($item['variant_name']): ?>
                                    <p class="text-sm text-gray-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($item['variant_name']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($item['custom_text'])): ?>
                                    <p class="text-xs text-blue-600 mt-2 font-medium bg-blue-50 inline-block px-2 py-1 rounded">Engraving: "<?php echo htmlspecialchars($item['custom_text']); ?>"</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex flex-col items-end gap-3 sm:gap-6 mt-4 sm:mt-0 w-full sm:w-auto">
                                <p class="font-bold text-pink-600 text-lg">₱<?php echo number_format($item['price'] * $item['qty'], 2); ?></p>
                                
                                <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end">
                                    <form method="POST" action="cart.php" class="flex items-center bg-gray-50 rounded-xl border border-gray-200">
                                        <input type="hidden" name="action" value="update_qty">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                        <input type="number" name="qty" value="<?php echo $item['qty']; ?>" min="1" class="w-16 bg-transparent text-center font-bold text-sm py-2 focus:outline-none" onchange="this.form.submit()">
                                    </form>
                                    
                                    <form method="POST" action="cart.php">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                        <button type="submit" class="text-gray-400 hover:text-red-500 transition p-2 bg-gray-50 hover:bg-red-50 rounded-full">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Order Summary -->
                <div class="w-full lg:w-1/3">
                    <div class="bg-white/80 backdrop-blur-md p-8 rounded-[2.5rem] shadow-xl border border-gray-100 sticky top-32">
                        <h2 class="text-xl font-bold text-gray-900 mb-6 uppercase tracking-widest text-sm border-b border-gray-100 pb-4">Order Summary</h2>
                        
                        <div class="space-y-4 text-sm mb-6">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal</span>
                                <span class="font-bold text-gray-900">₱<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <?php if ($voucher_applied): ?>
                            <div class="flex justify-between text-pink-600">
                                <span>Discount (<?php echo htmlspecialchars($_SESSION['voucher_code']); ?>)</span>
                                <span class="font-bold">-₱<?php echo number_format($discount, 2); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-6">
                            <?php if (!empty($voucher_message)): ?>
                                <p class="text-xs mb-2 <?php echo $voucher_applied ? 'text-green-600' : 'text-red-500'; ?>"><?php echo htmlspecialchars($voucher_message); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($voucher_applied): ?>
                                <form method="POST" action="cart.php" class="flex">
                                    <input type="hidden" name="action" value="remove_voucher">
                                    <button type="submit" class="w-full bg-red-50 text-red-600 py-2 rounded-xl text-xs font-bold uppercase tracking-widest border border-red-100 hover:bg-red-100 transition-colors">Remove Voucher</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="cart.php" class="flex space-x-2">
                                    <input type="hidden" name="action" value="apply_voucher">
                                    <input type="text" name="voucher_code" placeholder="Enter voucher code" class="flex-1 bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-pink-500" required>
                                    <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors">Apply</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <div class="border-t border-gray-100 pt-6 mb-8 flex justify-between items-end">
                            <span class="text-sm font-bold uppercase tracking-widest text-gray-500">Total</span>
                            <span class="text-3xl font-bold text-pink-600 leading-none">₱<?php echo number_format($total, 2); ?></span>
                        </div>
                        
                        <a href="checkout.php" class="block text-center w-full bg-gradient-to-r from-blue-600 to-pink-500 text-white py-4 rounded-2xl text-sm font-bold uppercase tracking-widest hover:opacity-90 shadow-xl shadow-pink-500/30 transition-all active:scale-[0.98]">
                            Proceed to Checkout
                        </a>
                        
                        <?php if (!$is_logged_in): ?>
                            <p class="text-center text-xs text-gray-500 mt-4">You will be asked to log in to complete your purchase.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-white/30 backdrop-blur-md border-t border-white/50 mt-auto py-10">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-500 text-sm">&copy; <?php echo date("Y"); ?> Pearlz Exclusive. All rights reserved.</p>
        </div>
    </footer>
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
