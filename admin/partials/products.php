<?php
// admin/partials/products.php
require_once '../databse/database.php';
require_once '../models/Product.php';

$database = new Database();
$db = $database->getConnection();
$productModel = new Product($db);

$action = $_POST['action'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($action === 'create' || $action === 'update') {
        $data = [
            'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
            'base_sku' => $_POST['base_sku'] ?? '',
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'base_price' => $_POST['base_price'] ?? 0,
            'is_limited_edition' => isset($_POST['is_limited_edition']) ? 1 : 0,
            'allows_custom_text' => isset($_POST['allows_custom_text']) ? 1 : 0,
            'custom_text_limit' => $_POST['custom_text_limit'] ?? 0
        ];

        if ($action === 'create') {
            $productModel->create($data);
        } else {
            $productModel->update($_POST['product_id'], $data);
        }
    } elseif ($action === 'delete') {
        $productModel->delete($_POST['product_id']);
    }
    
    // Redirect to clear POST data
    header("Location: layout.php?page=products");
    exit();
}

$products = $productModel->getAll();

// Fetch categories for the dropdown
$categories = $db->query("SELECT category_id, category_name FROM Categories")->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="flex flex-col md:flex-row justify-between items-left mb-8 space-y-4 md:space-y-0">
    <div>
        <h2 class="text-2xl font-bold text-gray-800 text-left">Product Management</h2>
        <p class="text-sm text-gray-500 mt-1">Add, update, or remove products from your catalog.</p>
    </div>
    <div class="flex items-center space-x-4">
        <!-- View Toggle Buttons -->
        <div class="bg-white border border-gray-200 rounded-full flex p-1 shadow-sm">
            <button onclick="setView('list')" id="btnList" class="p-2 rounded-full bg-gray-100 text-gray-800 transition-colors" title="List View">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <button onclick="setView('grid')" id="btnGrid" class="p-2 rounded-full text-gray-400 hover:text-gray-800 transition-colors" title="Grid View">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            </button>
        </div>
        <button onclick="openProductModal()" class="bg-gradient-to-r from-blue-600 to-pink-500 text-white px-6 py-2.5 rounded-full font-bold uppercase tracking-widest text-xs hover:opacity-90 shadow-lg transition-opacity flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>Add Product</span>
        </button>
    </div>
</div>

<div id="listView" class="overflow-x-auto bg-white rounded-3xl border border-gray-100 shadow-sm">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-50/50 border-b border-gray-100 text-gray-400 text-xs uppercase tracking-widest">
                <th class="p-5 font-semibold">Product</th>
                <th class="p-5 font-semibold">SKU</th>
                <th class="p-5 font-semibold">Category</th>
                <th class="p-5 font-semibold">Price</th>
                <th class="p-5 font-semibold text-center">Badges</th>
                <th class="p-5 font-semibold text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="text-sm text-gray-700 divide-y divide-gray-50">
            <?php if (count($products) > 0): ?>
                <?php foreach($products as $p): ?>
                <tr class="hover:bg-gray-50/50 transition-colors group cursor-pointer" onclick="window.location.href='layout.php?page=product_manage&id=<?php echo $p['product_id']; ?>'">
                    <td class="p-5">
                        <div class="flex items-center space-x-4">
                            <?php if (!empty($p['thumbnail'])): ?>
                                <img src="../<?php echo htmlspecialchars($p['thumbnail']); ?>" class="w-12 h-12 rounded-lg object-cover border border-gray-200 shadow-sm" alt="Thumbnail">
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-[10px] font-bold uppercase tracking-widest border border-gray-200">No Img</div>
                            <?php endif; ?>
                            <span class="font-bold text-gray-900"><?php echo htmlspecialchars($p['name']); ?></span>
                        </div>
                    </td>
                    <td class="p-5 font-mono text-gray-400 text-xs"><?php echo htmlspecialchars($p['base_sku']); ?></td>
                    <td class="p-5 text-gray-500"><?php echo htmlspecialchars($p['category_name'] ?? 'Uncategorized'); ?></td>
                    <td class="p-5 font-medium">₱<?php echo number_format($p['base_price'], 2); ?></td>
                    <td class="p-5 text-center flex flex-wrap justify-center gap-2">
                        <?php if($p['is_limited_edition']): ?>
                            <span class="bg-pink-50 text-pink-600 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-widest border border-pink-100">Limited</span>
                        <?php endif; ?>
                        <?php if($p['allows_custom_text']): ?>
                            <span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-widest border border-blue-100">Custom (<?php echo $p['custom_text_limit']; ?>)</span>
                        <?php endif; ?>
                        <?php if(!$p['is_limited_edition'] && !$p['allows_custom_text']): ?>
                            <span class="text-gray-300">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-5 text-right space-x-3" onclick="event.stopPropagation();">
                        <button type="button" onclick='editProduct(<?php echo json_encode($p); ?>)' class="text-gray-400 hover:text-blue-500 transition-colors inline-block">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <a href="layout.php?page=product_manage&id=<?php echo $p['product_id']; ?>" class="text-gray-400 hover:text-pink-500 transition-colors inline-block mr-1" title="Manage Variants & Images">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </a>
                        <form method="POST" action="layout.php?page=products" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this product?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                            <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="p-12 text-center text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <p class="text-lg font-medium text-gray-500">No products found.</p>
                        <p class="text-sm mt-1">Click "Add Product" to get started.</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Grid View -->
<div id="gridView" class="hidden grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
    <?php if (count($products) > 0): ?>
        <?php foreach($products as $p): ?>
            <div onclick="window.location.href='layout.php?page=product_manage&id=<?php echo $p['product_id']; ?>'" class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden flex flex-col group hover:shadow-lg transition-shadow cursor-pointer">
                <div class="h-48 w-full relative bg-gray-50 flex-shrink-0">
                    <?php if (!empty($p['thumbnail'])): ?>
                        <img src="../<?php echo htmlspecialchars($p['thumbnail']); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                            <svg class="w-12 h-12 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                    <?php endif; ?>
                    <div class="absolute top-3 right-3 flex space-x-2 opacity-0 group-hover:opacity-100 transition-opacity" onclick="event.stopPropagation();">
                        <button type="button" onclick='editProduct(<?php echo json_encode($p); ?>)' class="bg-white text-gray-600 hover:text-blue-500 p-2 rounded-full shadow transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <a href="layout.php?page=product_manage&id=<?php echo $p['product_id']; ?>" class="bg-white text-gray-600 hover:text-pink-500 p-2 rounded-full shadow transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </a>
                        <form method="POST" action="layout.php?page=products" onsubmit="return confirm('Are you sure you want to delete this product?');" class="inline-block m-0">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                            <button type="submit" class="bg-white text-gray-600 hover:text-red-500 p-2 rounded-full shadow transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="p-5 flex flex-col flex-grow">
                    <div class="text-xs font-mono text-gray-400 mb-1"><?php echo htmlspecialchars($p['base_sku']); ?></div>
                    <h3 class="font-bold text-gray-900 text-lg mb-1 truncate" title="<?php echo htmlspecialchars($p['name']); ?>"><?php echo htmlspecialchars($p['name']); ?></h3>
                    <div class="text-sm text-gray-500 mb-4"><?php echo htmlspecialchars($p['category_name'] ?? 'Uncategorized'); ?></div>
                    <div class="mt-auto flex justify-between items-center">
                        <div class="font-bold text-gray-900 text-lg">₱<?php echo number_format($p['base_price'], 2); ?></div>
                        <div class="flex space-x-1">
                            <?php if($p['is_limited_edition']): ?>
                                <span class="bg-pink-50 text-pink-600 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-widest border border-pink-100" title="Limited Edition">LE</span>
                            <?php endif; ?>
                            <?php if($p['allows_custom_text']): ?>
                                <span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-widest border border-blue-100" title="Custom Text">CST</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-full p-12 text-center text-gray-400 bg-white rounded-3xl border border-gray-100">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            <p class="text-lg font-medium text-gray-500">No products found.</p>
            <p class="text-sm mt-1">Click "Add Product" to get started.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Product Modal -->
<div id="productModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl transform scale-95 transition-transform duration-300 mx-4 flex flex-col max-h-[90vh] overflow-hidden" id="productModalContent">
        <div class="flex justify-between items-center p-8 pb-4 border-b border-gray-100 bg-white z-10 flex-shrink-0">
            <h3 class="text-xl font-bold text-gray-900 uppercase tracking-widest" id="modalTitle">Add New Product</h3>
            <button type="button" onclick="closeProductModal()" class="text-gray-400 hover:text-pink-500 transition-colors bg-gray-50 hover:bg-pink-50 p-2 rounded-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <div class="overflow-y-auto flex-1">
            <div class="p-8 pt-6">
                <form method="POST" action="layout.php?page=products" class="space-y-6">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="product_id" id="product_id" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-500 text-xs font-bold uppercase tracking-widest mb-2">Base SKU *</label>
                    <input type="text" name="base_sku" id="base_sku" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all font-mono" placeholder="e.g. BRAC-001">
                </div>
                <div>
                    <label class="block text-gray-500 text-xs font-bold uppercase tracking-widest mb-2">Product Name *</label>
                    <input type="text" name="name" id="name" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all" placeholder="Signature Cuff">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-500 text-xs font-bold uppercase tracking-widest mb-2">Category</label>
                    <select name="category_id" id="category_id" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all appearance-none">
                        <option value="">-- Select Category --</option>
                        <?php foreach($categories as $c): ?>
                            <option value="<?php echo $c['category_id']; ?>"><?php echo htmlspecialchars($c['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-500 text-xs font-bold uppercase tracking-widest mb-2">Base Price (₱) *</label>
                    <input type="number" step="0.01" name="base_price" id="base_price" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all" placeholder="0.00">
                </div>
            </div>

            <div>
                <label class="block text-gray-500 text-xs font-bold uppercase tracking-widest mb-2">Description</label>
                <textarea name="description" id="description" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all" placeholder="Product details..."></textarea>
            </div>

            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                <h4 class="text-xs font-bold uppercase tracking-widest text-gray-900 mb-4">Product Configuration</h4>
                <div class="flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-8">
                    <label class="flex items-center space-x-3 cursor-pointer group">
                        <input type="checkbox" name="is_limited_edition" id="is_limited_edition" class="w-5 h-5 rounded border-gray-300 text-pink-500 focus:ring-pink-400 cursor-pointer transition-all">
                        <span class="text-sm font-semibold text-gray-700 group-hover:text-pink-600 transition-colors">Limited Edition</span>
                    </label>
                    
                    <label class="flex items-center space-x-3 cursor-pointer group">
                        <input type="checkbox" name="allows_custom_text" id="allows_custom_text" class="w-5 h-5 rounded border-gray-300 text-blue-500 focus:ring-blue-400 cursor-pointer transition-all" onchange="document.getElementById('custom_limit_div').classList.toggle('hidden', !this.checked)">
                        <span class="text-sm font-semibold text-gray-700 group-hover:text-blue-600 transition-colors">Allows Custom Text</span>
                    </label>
                </div>

                <div id="custom_limit_div" class="hidden mt-6 pt-4 border-t border-gray-200">
                    <label class="block text-gray-500 text-xs font-bold uppercase tracking-widest mb-2">Max Character Limit</label>
                    <input type="number" name="custom_text_limit" id="custom_text_limit" value="0" class="w-full md:w-1/3 bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-all">
                </div>
            </div>

            <div class="pt-6 flex justify-end space-x-4">
                <button type="button" onclick="closeProductModal()" class="px-8 py-3 rounded-full text-gray-500 font-bold uppercase tracking-widest text-xs hover:bg-gray-100 hover:text-gray-900 transition-colors">Cancel</button>
                <button type="submit" class="bg-gradient-to-r from-blue-600 to-pink-500 text-white px-8 py-3 rounded-full font-bold uppercase tracking-widest text-xs hover:opacity-90 shadow-xl shadow-pink-500/20 transition-opacity">Save Product</button>
            </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openProductModal() {
    const modal = document.getElementById('productModal');
    const content = document.getElementById('productModalContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        content.classList.remove('scale-95');
        content.classList.add('scale-100');
    }, 10);
}

function closeProductModal() {
    const modal = document.getElementById('productModal');
    const content = document.getElementById('productModalContent');
    modal.classList.add('opacity-0');
    content.classList.remove('scale-100');
    content.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        // Reset form
        document.querySelector('#productModal form').reset();
        document.getElementById('formAction').value = 'create';
        document.getElementById('product_id').value = '';
        document.getElementById('modalTitle').textContent = 'Add New Product';
        document.getElementById('custom_limit_div').classList.add('hidden');
    }, 300);
}

function editProduct(product) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('product_id').value = product.product_id;
    document.getElementById('base_sku').value = product.base_sku;
    document.getElementById('name').value = product.name;
    document.getElementById('category_id').value = product.category_id || '';
    document.getElementById('base_price').value = product.base_price;
    document.getElementById('description').value = product.description;
    
    document.getElementById('is_limited_edition').checked = product.is_limited_edition == 1;
    
    const allowsCustom = product.allows_custom_text == 1;
    document.getElementById('allows_custom_text').checked = allowsCustom;
    document.getElementById('custom_text_limit').value = product.custom_text_limit;
    
    document.getElementById('custom_limit_div').classList.toggle('hidden', !allowsCustom);
    document.getElementById('modalTitle').textContent = 'Edit Product';
    
    openProductModal();
}

function setView(viewMode) {
    const listView = document.getElementById('listView');
    const gridView = document.getElementById('gridView');
    const btnList = document.getElementById('btnList');
    const btnGrid = document.getElementById('btnGrid');

    if (viewMode === 'list') {
        listView.classList.remove('hidden');
        gridView.classList.add('hidden');
        btnList.classList.add('bg-gray-100', 'text-gray-800');
        btnList.classList.remove('text-gray-400');
        btnGrid.classList.remove('bg-gray-100', 'text-gray-800');
        btnGrid.classList.add('text-gray-400');
        localStorage.setItem('productViewMode', 'list');
    } else {
        gridView.classList.remove('hidden');
        listView.classList.add('hidden');
        btnGrid.classList.add('bg-gray-100', 'text-gray-800');
        btnGrid.classList.remove('text-gray-400');
        btnList.classList.remove('bg-gray-100', 'text-gray-800');
        btnList.classList.add('text-gray-400');
        localStorage.setItem('productViewMode', 'grid');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const savedMode = localStorage.getItem('productViewMode') || 'list';
    setView(savedMode);
});
</script>
