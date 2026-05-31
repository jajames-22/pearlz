<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../../databse/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid data payload.']);
    exit();
}

$cart = $data['cart'] ?? [];
$paymentMode = $data['paymentMode'] ?? 'cash';
$customerName = $data['customerName'] ?? '';
$refNo = $data['refNo'] ?? '';
$totalAmount = $data['totalAmount'] ?? 0;
$amountPaid = $data['amountPaid'] ?? 0;

if (empty($cart) || $totalAmount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty or total is invalid.']);
    exit();
}

try {
    $db->beginTransaction();

    // 1. Insert into Orders
    $guest_first_name = null;
    $guest_last_name = null;
    if (!empty($customerName)) {
        $parts = explode(' ', $customerName, 2);
        $guest_first_name = $parts[0];
        if (isset($parts[1])) {
            $guest_last_name = $parts[1];
        }
    }

    $orderQuery = "INSERT INTO Orders (user_id, order_source, guest_first_name, guest_last_name, total_amount, status) 
                   VALUES (NULL, 'in_person_pos', :fn, :ln, :total, 'Completed')";
    $stmt = $db->prepare($orderQuery);
    $stmt->execute([
        ':fn' => $guest_first_name,
        ':ln' => $guest_last_name,
        ':total' => $totalAmount
    ]);
    
    $order_id = $db->lastInsertId();

    // 2. Insert into Order_Items and update stock
    foreach ($cart as $item) {
        $variant_id = $item['variant_id'];
        $qty = $item['qty'];
        $price = $item['price'];

        $itemQuery = "INSERT INTO Order_Items (order_id, variant_id, quantity, unit_price) 
                      VALUES (:oid, :vid, :qty, :price)";
        $stmtItem = $db->prepare($itemQuery);
        $stmtItem->execute([
            ':oid' => $order_id,
            ':vid' => $variant_id,
            ':qty' => $qty,
            ':price' => $price
        ]);

        // Reduce stock in Product_Variants (allow negative if cashier overrides physical limits)
        $stockQuery = "UPDATE Product_Variants SET stock_quantity = stock_quantity - :qty 
                       WHERE variant_id = :vid";
        $stmtStock = $db->prepare($stockQuery);
        $stmtStock->execute([
            ':qty' => $qty,
            ':vid' => $variant_id
        ]);
    }

    // 3. Insert into Payments
    $payment_method = ($paymentMode === 'online') ? 'GCash/Online' : 'Cash';
    $transaction_id = ($paymentMode === 'online') ? $refNo : null;
    
    // Fallback amountPaid for GCash just in case it was passed as 0
    if ($paymentMode === 'online' && $amountPaid < $totalAmount) {
        $amountPaid = $totalAmount;
    }
    
    $payQuery = "INSERT INTO Payments (order_id, payment_method, transaction_id, amount, payment_status) 
                 VALUES (:oid, :pm, :tid, :amt, 'Completed')";
    $stmtPay = $db->prepare($payQuery);
    $stmtPay->execute([
        ':oid' => $order_id,
        ':pm' => $payment_method,
        ':tid' => $transaction_id,
        ':amt' => $amountPaid
    ]);

    $db->commit();
    echo json_encode(['success' => true, 'order_id' => $order_id]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
