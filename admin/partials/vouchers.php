<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') exit();

require_once '../databse/database.php';
$db = (new Database())->getConnection();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $code = strtoupper(trim($_POST['code']));
        $type = $_POST['discount_type'];
        $val = (float)$_POST['discount_value'];
        $min = (float)$_POST['min_spend'];
        $active = isset($_POST['is_active']) ? 1 : 0;
        $max_uses = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;

        try {
            $stmt = $db->prepare("INSERT INTO vouchers (code, discount_type, discount_value, min_spend, is_active, max_uses) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $type, $val, $min, $active, $max_uses]);
            $message = "Voucher created successfully.";
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['voucher_id'];
        $code = strtoupper(trim($_POST['code']));
        $type = $_POST['discount_type'];
        $val = (float)$_POST['discount_value'];
        $min = (float)$_POST['min_spend'];
        $active = isset($_POST['is_active']) ? 1 : 0;
        $max_uses = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;

        try {
            $stmt = $db->prepare("UPDATE vouchers SET code=?, discount_type=?, discount_value=?, min_spend=?, is_active=?, max_uses=? WHERE voucher_id=?");
            $stmt->execute([$code, $type, $val, $min, $active, $max_uses, $id]);
            $message = "Voucher updated successfully.";
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['voucher_id'];
        $stmt = $db->prepare("DELETE FROM vouchers WHERE voucher_id=?");
        $stmt->execute([$id]);
        $message = "Voucher deleted successfully.";
    }
}

$stmt = $db->query("SELECT * FROM vouchers ORDER BY voucher_id DESC");
$vouchers = $stmt->fetchAll();
?>

<div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Voucher Management</h2>
            <p class="text-gray-500 text-sm mt-1">Create and manage discount codes for customers.</p>
        </div>
        <button onclick="openVoucherModal('add')" class="bg-gradient-to-r from-blue-600 to-pink-500 text-white px-6 py-2.5 rounded-full text-sm font-bold uppercase tracking-widest hover:opacity-90 transition-opacity shadow-lg">
            + New Voucher
        </button>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-xl text-sm font-medium <?php echo strpos($message, 'Error') !== false ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-widest text-gray-500">Code</th>
                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-widest text-gray-500">Discount</th>
                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-widest text-gray-500">Min Spend</th>
                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-widest text-gray-500">Usage</th>
                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-widest text-gray-500">Status</th>
                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-widest text-gray-500 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($vouchers as $v): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-4 font-bold text-gray-900"><?php echo htmlspecialchars($v['code']); ?></td>
                        <td class="py-4 px-4 text-sm text-gray-600">
                            <?php echo $v['discount_type'] === 'percentage' ? htmlspecialchars($v['discount_value']).'%' : '₱'.number_format($v['discount_value'], 2); ?>
                        </td>
                        <td class="py-4 px-4 text-sm text-gray-600">₱<?php echo number_format($v['min_spend'], 2); ?></td>
                        <td class="py-4 px-4 text-sm text-gray-600">
                            <span class="font-bold text-gray-900"><?php echo $v['used_count']; ?></span> / <?php echo $v['max_uses'] ? $v['max_uses'] : '&infin;'; ?>
                        </td>
                        <td class="py-4 px-4">
                            <?php if ($v['is_active']): ?>
                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">Active</span>
                            <?php else: ?>
                                <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4 text-right">
                            <button onclick='openVoucherModal("edit", <?php echo json_encode($v); ?>)' class="text-blue-500 hover:text-blue-700 text-sm font-bold uppercase tracking-widest mr-3">Edit</button>
                            <form method="POST" class="inline-block" onsubmit="return confirm('Delete this voucher?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="voucher_id" value="<?php echo $v['voucher_id']; ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-bold uppercase tracking-widest">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($vouchers)): ?>
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">No vouchers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Voucher Modal -->
<div id="voucherModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white/95 backdrop-blur-md p-8 rounded-3xl shadow-2xl w-full max-w-lg transform scale-95 transition-transform duration-300 mx-4" id="voucherModalContent">
        <h2 id="modalTitle" class="text-2xl font-bold text-gray-900 mb-6">Add Voucher</h2>
        
        <form method="POST" action="layout.php?page=vouchers">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="voucher_id" id="voucherId" value="">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Voucher Code</label>
                    <input type="text" name="code" id="voucherCode" required class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-500 focus:ring-1 focus:ring-pink-500 transition-all uppercase">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Discount Type</label>
                        <select name="discount_type" id="discountType" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-500 transition-all">
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (₱)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Discount Value</label>
                        <input type="number" step="0.01" min="0" name="discount_value" id="discountValue" required class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-500 transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Min Spend (₱)</label>
                        <input type="number" step="0.01" min="0" name="min_spend" id="minSpend" value="0" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Max Uses (optional)</label>
                        <input type="number" step="1" min="1" name="max_uses" id="maxUses" placeholder="Unlimited" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-500 transition-all">
                    </div>
                </div>

                <div class="flex items-center space-x-3 pt-2">
                    <input type="checkbox" name="is_active" id="isActive" value="1" checked class="w-5 h-5 text-pink-500 rounded border-gray-300 focus:ring-pink-500">
                    <label for="isActive" class="text-sm font-medium text-gray-700">Voucher is Active</label>
                </div>
            </div>
            
            <div class="mt-8 flex justify-end space-x-4">
                <button type="button" onclick="closeVoucherModal()" class="px-6 py-2.5 rounded-full text-sm font-bold uppercase tracking-widest text-gray-600 hover:bg-gray-100 transition-colors">Cancel</button>
                <button type="submit" class="bg-gray-900 text-white px-6 py-2.5 rounded-full text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors shadow-lg">Save Voucher</button>
            </div>
        </form>
    </div>
</div>

<script>
    const vModal = document.getElementById('voucherModal');
    const vContent = document.getElementById('voucherModalContent');

    function openVoucherModal(mode, data = null) {
        if (mode === 'edit' && data) {
            document.getElementById('modalTitle').textContent = 'Edit Voucher';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('voucherId').value = data.voucher_id;
            document.getElementById('voucherCode').value = data.code;
            document.getElementById('discountType').value = data.discount_type;
            document.getElementById('discountValue').value = data.discount_value;
            document.getElementById('minSpend').value = data.min_spend;
            document.getElementById('maxUses').value = data.max_uses || '';
            document.getElementById('isActive').checked = data.is_active == 1;
        } else {
            document.getElementById('modalTitle').textContent = 'Add Voucher';
            document.getElementById('formAction').value = 'add';
            document.getElementById('voucherId').value = '';
            document.getElementById('voucherCode').value = '';
            document.getElementById('discountType').value = 'percentage';
            document.getElementById('discountValue').value = '';
            document.getElementById('minSpend').value = '0';
            document.getElementById('maxUses').value = '';
            document.getElementById('isActive').checked = true;
        }

        vModal.classList.remove('hidden');
        setTimeout(() => {
            vModal.classList.remove('opacity-0');
            vContent.classList.remove('scale-95');
            vContent.classList.add('scale-100');
        }, 10);
    }

    function closeVoucherModal() {
        vModal.classList.add('opacity-0');
        vContent.classList.remove('scale-100');
        vContent.classList.add('scale-95');
        setTimeout(() => {
            vModal.classList.add('hidden');
        }, 300);
    }

    vModal.addEventListener('click', (e) => {
        if (e.target === vModal) closeVoucherModal();
    });
</script>
