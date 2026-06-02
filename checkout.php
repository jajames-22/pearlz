<?php
// checkout.php
session_start();

$is_logged_in = isset($_SESSION['user_id']);
if (!$is_logged_in) {
    header("Location: index.php");
    exit();
}

require_once 'databse/database.php';
$db = (new Database())->getConnection();

// Fetch cart items
$stmt = $db->prepare("
    SELECT c.cart_id, c.qty, c.variant_id, c.custom_text, p.name, p.base_price, 
           v.variant_name, v.price_override
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    LEFT JOIN product_variants v ON c.variant_id = v.variant_id
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

$subtotal = 0;
foreach ($cart_items as &$item) {
    $price = $item['price_override'] !== null ? $item['price_override'] : $item['base_price'];
    $item['price'] = $price;
    $subtotal += $price * $item['qty'];
}

$total = $subtotal;
$discount = 0;

if (isset($_SESSION['voucher_code'])) {
    $stmt = $db->prepare("SELECT * FROM vouchers WHERE code = ? AND is_active = 1");
    $stmt->execute([$_SESSION['voucher_code']]);
    $voucher = $stmt->fetch();
    
    if ($voucher && ($voucher['max_uses'] === null || $voucher['used_count'] < $voucher['max_uses']) && $subtotal >= $voucher['min_spend']) {
        if ($voucher['discount_type'] === 'percentage') {
            $discount = $subtotal * ($voucher['discount_value'] / 100);
        } else {
            $discount = $voucher['discount_value'];
        }
        if ($discount > $subtotal) $discount = $subtotal;
        $total = $subtotal - $discount;
    } else {
        unset($_SESSION['voucher_code']);
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_type = $_POST['delivery_type'] ?? 'pickup';
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $preferred_date = $_POST['preferred_date'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'cash_on_pickup';
    $payment_reference = trim($_POST['payment_reference'] ?? '');

    if ($delivery_type === 'delivery' && empty($delivery_address)) {
        $error = "Delivery address is required for delivery.";
    } elseif ($payment_method === 'online_payment' && empty($payment_reference)) {
        $error = "Payment reference number is required for online payment.";
    } elseif (empty($preferred_date)) {
        $error = "Preferred date and time are required.";
    } else {
        try {
            $db->beginTransaction();

            $stmt = $db->prepare("INSERT INTO Orders (user_id, order_source, total_amount, status, delivery_type, delivery_address, preferred_date, payment_method, payment_reference) VALUES (?, 'online', ?, 'Pending', ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $total, $delivery_type, $delivery_address, $preferred_date, $payment_method, $payment_reference]);
            $order_id = $db->lastInsertId();

            foreach ($cart_items as $item) {
                $stmt = $db->prepare("INSERT INTO Order_Items (order_id, variant_id, quantity, unit_price, custom_text) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['variant_id'], $item['qty'], $item['price'], $item['custom_text']]);

                $stmt = $db->prepare("UPDATE Product_Variants SET stock_quantity = stock_quantity - ? WHERE variant_id = ?");
                $stmt->execute([$item['qty'], $item['variant_id']]);
            }

            if ($payment_method === 'online_payment') {
                $stmt = $db->prepare("INSERT INTO Payments (order_id, payment_method, transaction_id, amount, payment_status) VALUES (?, 'Online', ?, ?, 'Pending')");
                $stmt->execute([$order_id, $payment_reference, $total]);
            }

            if (isset($_SESSION['voucher_code'])) {
                $stmt = $db->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE code = ?");
                $stmt->execute([$_SESSION['voucher_code']]);
                unset($_SESSION['voucher_code']);
            }

            $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);

            $db->commit();

            header("Location: cart.php?success=order_placed");
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            $error = "Failed to place order. " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Pearlz</title>
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
            <a href="cart.php" class="text-pink-500 hover:text-pink-600 transition-colors uppercase font-semibold">Back to Cart</a>
        </div>
    </header>

    <main class="flex-1 max-w-4xl mx-auto px-4 py-12 md:py-16 w-full">
        <h1 class="text-4xl font-bold text-gray-900 mb-10 text-center">Secure Checkout</h1>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-500 border border-red-200 p-4 rounded-xl mb-8 text-sm font-bold text-center">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white/80 backdrop-blur-md p-8 md:p-12 rounded-[2.5rem] shadow-xl border border-gray-100">
            <form method="POST" action="checkout.php" class="space-y-8">
                
                <!-- Order Summary (Read-Only) -->
                <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-200 pb-2">Order Summary</h3>
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Subtotal</span>
                        <span>₱<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <?php if ($discount > 0): ?>
                    <div class="flex justify-between text-sm text-pink-600 mb-2">
                        <span>Discount</span>
                        <span>-₱<?php echo number_format($discount, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between font-bold text-xl text-gray-900 mt-4 pt-4 border-t border-gray-200">
                        <span>Total to Pay</span>
                        <span class="text-pink-600">₱<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-4">Delivery Method</h3>
                            <div class="space-y-3">
                                <label class="flex items-center space-x-3 cursor-pointer group">
                                    <input type="radio" name="delivery_type" value="pickup" checked onchange="toggleDeliveryOptions()" class="w-5 h-5 text-pink-500 border-gray-300 focus:ring-pink-500">
                                    <span class="text-gray-700 font-medium group-hover:text-pink-600 transition-colors">Store Pickup</span>
                                </label>
                                <label class="flex items-center space-x-3 cursor-pointer group">
                                    <input type="radio" name="delivery_type" value="delivery" onchange="toggleDeliveryOptions()" class="w-5 h-5 text-pink-500 border-gray-300 focus:ring-pink-500">
                                    <span class="text-gray-700 font-medium group-hover:text-pink-600 transition-colors">Delivery</span>
                                </label>
                            </div>
                        </div>

                        <div id="addressField" class="hidden">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Delivery Address</label>
                            <textarea name="delivery_address" rows="3" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-500 focus:ring-1 focus:ring-pink-500 transition-all placeholder-gray-400" placeholder="Enter your full address"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Preferred Date & Time</label>
                            <input type="datetime-local" name="preferred_date" required class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-500 focus:ring-1 focus:ring-pink-500 transition-all text-gray-700">
                            <p class="text-xs text-gray-500 mt-1">Admin will confirm if this time and place is possible.</p>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-4">Payment Method</h3>
                            <div class="space-y-3" id="paymentOptions">
                                <label class="flex items-center space-x-3 cursor-pointer group" id="optCop">
                                    <input type="radio" name="payment_method" value="cash_on_pickup" checked onchange="togglePaymentOptions()" class="w-5 h-5 text-pink-500 border-gray-300 focus:ring-pink-500">
                                    <span class="text-gray-700 font-medium group-hover:text-pink-600 transition-colors">Cash on Pickup</span>
                                </label>
                                <label class="flex items-center space-x-3 cursor-pointer group hidden" id="optCod">
                                    <input type="radio" name="payment_method" value="cash_on_delivery" onchange="togglePaymentOptions()" class="w-5 h-5 text-pink-500 border-gray-300 focus:ring-pink-500">
                                    <span class="text-gray-700 font-medium group-hover:text-pink-600 transition-colors">Cash on Delivery</span>
                                </label>
                                <label class="flex items-center space-x-3 cursor-pointer group">
                                    <input type="radio" name="payment_method" value="online_payment" onchange="togglePaymentOptions()" class="w-5 h-5 text-pink-500 border-gray-300 focus:ring-pink-500">
                                    <span class="text-gray-700 font-medium group-hover:text-pink-600 transition-colors">Online Payment</span>
                                </label>
                            </div>
                        </div>

                        <div id="onlinePaymentField" class="hidden bg-blue-50 p-6 rounded-2xl border border-blue-100">
                            <h4 class="font-bold text-blue-900 mb-2 text-sm uppercase tracking-widest">GCash Details</h4>
                            <p class="text-sm text-blue-800 mb-1">Account Name: <strong>Pearlz Store</strong></p>
                            <p class="text-sm text-blue-800 mb-4">Account Number: <strong>0912 345 6789</strong></p>
                            
                            <label class="block text-sm font-bold text-gray-700 mb-2">Reference Number</label>
                            <input type="text" name="payment_reference" placeholder="Enter Ref No." class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-500 focus:ring-1 focus:ring-pink-500 transition-all">
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-100">
                    <button type="submit" class="w-full md:w-auto md:px-12 bg-gradient-to-r from-blue-600 to-pink-500 text-white py-4 rounded-full text-sm font-bold uppercase tracking-widest hover:opacity-90 shadow-xl shadow-pink-500/30 transition-all active:scale-[0.98]">
                        Place Order
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function toggleDeliveryOptions() {
            const isDelivery = document.querySelector('input[name="delivery_type"][value="delivery"]').checked;
            const addressField = document.getElementById('addressField');
            const optCop = document.getElementById('optCop');
            const optCod = document.getElementById('optCod');
            
            if (isDelivery) {
                addressField.classList.remove('hidden');
                optCop.classList.add('hidden');
                optCod.classList.remove('hidden');
                
                // If currently cash on pickup, switch to cash on delivery
                if (document.querySelector('input[name="payment_method"][value="cash_on_pickup"]').checked) {
                    document.querySelector('input[name="payment_method"][value="cash_on_delivery"]').checked = true;
                }
            } else {
                addressField.classList.add('hidden');
                optCop.classList.remove('hidden');
                optCod.classList.add('hidden');
                
                // If currently cash on delivery, switch to cash on pickup
                if (document.querySelector('input[name="payment_method"][value="cash_on_delivery"]').checked) {
                    document.querySelector('input[name="payment_method"][value="cash_on_pickup"]').checked = true;
                }
            }
            togglePaymentOptions();
        }

        function togglePaymentOptions() {
            const isOnline = document.querySelector('input[name="payment_method"][value="online_payment"]').checked;
            const onlinePaymentField = document.getElementById('onlinePaymentField');
            
            if (isOnline) {
                onlinePaymentField.classList.remove('hidden');
            } else {
                onlinePaymentField.classList.add('hidden');
            }
        }
        
        // Initialize state
        toggleDeliveryOptions();
    </script>
</body>
</html>
