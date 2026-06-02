<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') exit();

require_once '../databse/database.php';
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $order_id = (int)$_POST['order_id'];
    $action = $_POST['action'];

    if ($action === 'confirm') {
        $stmt = $db->prepare("UPDATE Orders SET status = 'Confirmed' WHERE order_id = ?");
        $stmt->execute([$order_id]);
    } elseif ($action === 'reject') {
        $stmt = $db->prepare("UPDATE Orders SET status = 'Rejected' WHERE order_id = ?");
        $stmt->execute([$order_id]);
    } elseif ($action === 'complete') {
        $stmt = $db->prepare("UPDATE Orders SET status = 'Completed' WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        $stmt = $db->prepare("UPDATE Payments SET payment_status = 'Completed' WHERE order_id = ?");
        $stmt->execute([$order_id]);
    }
}

$stmt = $db->query("
    SELECT o.*, u.first_name, u.last_name, u.email 
    FROM Orders o 
    LEFT JOIN Users u ON o.user_id = u.user_id 
    WHERE o.order_source = 'online'
    ORDER BY o.order_date DESC
");
$orders = $stmt->fetchAll();
?>

<div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800">Online Orders</h2>
        <p class="text-gray-500 text-sm mt-1">Review orders and confirm if the preferred date/time and location are possible.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-widest text-gray-500">Order ID</th>
                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-widest text-gray-500">Customer</th>
                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-widest text-gray-500">Type & Time</th>
                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-widest text-gray-500">Payment</th>
                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-widest text-gray-500">Total</th>
                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-widest text-gray-500">Status</th>
                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-widest text-gray-500 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($orders as $o): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-4 font-bold text-gray-900">#<?php echo $o['order_id']; ?></td>
                        <td class="py-4 px-4 text-sm text-gray-600">
                            <?php echo htmlspecialchars($o['first_name'] . ' ' . $o['last_name']); ?><br>
                            <span class="text-xs text-gray-400"><?php echo htmlspecialchars($o['email']); ?></span>
                        </td>
                        <td class="py-4 px-4 text-sm text-gray-600">
                            <span class="font-bold uppercase tracking-wider text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded"><?php echo $o['delivery_type']; ?></span><br>
                            <?php if ($o['preferred_date']): ?>
                                <?php echo date("M d, Y h:i A", strtotime($o['preferred_date'])); ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4 text-sm text-gray-600">
                            <?php 
                                $pm = str_replace('_', ' ', $o['payment_method']); 
                                echo ucwords($pm); 
                            ?><br>
                            <?php if ($o['payment_method'] === 'online_payment' && $o['payment_reference']): ?>
                                <span class="text-xs text-gray-500 font-bold">Ref: <?php echo htmlspecialchars($o['payment_reference']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4 font-bold text-gray-900">₱<?php echo number_format($o['total_amount'], 2); ?></td>
                        <td class="py-4 px-4">
                            <?php 
                            $statusClass = 'bg-gray-100 text-gray-500';
                            if ($o['status'] === 'Pending') $statusClass = 'bg-yellow-100 text-yellow-700';
                            if ($o['status'] === 'Confirmed') $statusClass = 'bg-blue-100 text-blue-700';
                            if ($o['status'] === 'Completed') $statusClass = 'bg-green-100 text-green-700';
                            if ($o['status'] === 'Rejected') $statusClass = 'bg-red-100 text-red-700';
                            ?>
                            <span class="<?php echo $statusClass; ?> px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider"><?php echo $o['status']; ?></span>
                        </td>
                        <td class="py-4 px-4 text-right space-x-2">
                            <button onclick='openOrderDetailsModal(<?php echo json_encode($o); ?>)' class="text-gray-500 hover:text-gray-700 text-sm font-bold uppercase tracking-widest">Details</button>
                            
                            <?php if ($o['status'] === 'Pending'): ?>
                                <form method="POST" class="inline-block" onsubmit="return confirm('Confirm this order?');">
                                    <input type="hidden" name="action" value="confirm">
                                    <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                                    <button type="submit" class="text-blue-500 hover:text-blue-700 text-sm font-bold uppercase tracking-widest">Confirm</button>
                                </form>
                                <form method="POST" class="inline-block" onsubmit="return confirm('Reject this order?');">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-bold uppercase tracking-widest">Reject</button>
                                </form>
                            <?php elseif ($o['status'] === 'Confirmed'): ?>
                                <form method="POST" class="inline-block" onsubmit="return confirm('Mark as Completed?');">
                                    <input type="hidden" name="action" value="complete">
                                    <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                                    <button type="submit" class="text-green-500 hover:text-green-700 text-sm font-bold uppercase tracking-widest">Complete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" class="py-8 text-center text-gray-500">No online orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white/95 backdrop-blur-md p-8 rounded-3xl shadow-2xl w-full max-w-2xl transform scale-95 transition-transform duration-300 mx-4" id="orderDetailsModalContent">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Order Details <span id="modalOrderId" class="text-pink-500"></span></h2>
        
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <h4 class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-1">Customer Details</h4>
                <p id="modalCustomer" class="text-sm text-gray-900 font-medium"></p>
                <p id="modalCustomerEmail" class="text-sm text-gray-500"></p>
            </div>
            <div>
                <h4 class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-1">Logistics</h4>
                <p id="modalDeliveryType" class="text-sm text-gray-900 font-medium uppercase"></p>
                <p id="modalDate" class="text-sm text-gray-500"></p>
                <p id="modalAddress" class="text-sm text-gray-500 mt-1"></p>
            </div>
        </div>

        <div class="mb-8">
            <h4 class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-1">Payment Info</h4>
            <p id="modalPaymentMethod" class="text-sm text-gray-900 font-medium uppercase"></p>
            <p id="modalPaymentRef" class="text-sm text-gray-500"></p>
        </div>
        
        <div class="mt-8 flex justify-end space-x-4 border-t border-gray-100 pt-6">
            <button type="button" onclick="closeOrderDetailsModal()" class="px-6 py-2.5 rounded-full text-sm font-bold uppercase tracking-widest text-gray-600 hover:bg-gray-100 transition-colors">Close</button>
        </div>
    </div>
</div>

<script>
    const orderModal = document.getElementById('orderDetailsModal');
    const orderContent = document.getElementById('orderDetailsModalContent');

    function openOrderDetailsModal(data) {
        document.getElementById('modalOrderId').textContent = '#' + data.order_id;
        document.getElementById('modalCustomer').textContent = data.first_name + ' ' + data.last_name;
        document.getElementById('modalCustomerEmail').textContent = data.email;
        
        document.getElementById('modalDeliveryType').textContent = data.delivery_type;
        
        if(data.preferred_date) {
            const date = new Date(data.preferred_date);
            document.getElementById('modalDate').textContent = date.toLocaleString();
        } else {
            document.getElementById('modalDate').textContent = 'No date provided';
        }

        if (data.delivery_type === 'delivery') {
            document.getElementById('modalAddress').textContent = 'Address: ' + (data.delivery_address || 'Not provided');
        } else {
            document.getElementById('modalAddress').textContent = '';
        }

        const pm = data.payment_method.replace(/_/g, ' ');
        document.getElementById('modalPaymentMethod').textContent = pm;
        
        if (data.payment_method === 'online_payment' && data.payment_reference) {
            document.getElementById('modalPaymentRef').textContent = 'Ref No: ' + data.payment_reference;
        } else {
            document.getElementById('modalPaymentRef').textContent = '';
        }

        orderModal.classList.remove('hidden');
        setTimeout(() => {
            orderModal.classList.remove('opacity-0');
            orderContent.classList.remove('scale-95');
            orderContent.classList.add('scale-100');
        }, 10);
    }

    function closeOrderDetailsModal() {
        orderModal.classList.add('opacity-0');
        orderContent.classList.remove('scale-100');
        orderContent.classList.add('scale-95');
        setTimeout(() => {
            orderModal.classList.add('hidden');
        }, 300);
    }

    orderModal.addEventListener('click', (e) => {
        if (e.target === orderModal) closeOrderDetailsModal();
    });
</script>
