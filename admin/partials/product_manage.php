<?php
// admin/partials/product_manage.php
require_once '../databse/database.php';
require_once '../models/Product.php';

$database = new Database();
$db = $database->getConnection();
$productModel = new Product($db);

$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    echo "<script>window.location.href='layout.php?page=products';</script>";
    exit();
}

$product = $productModel->getById($product_id);
if (!$product) {
    echo "<p class='text-red-500'>Product not found.</p>";
    exit();
}

$action = $_POST['action'] ?? '';
$upload_dir = '../uploads/products/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($action === 'add_variant') {
        $sku = $_POST['variant_sku'] ?? '';
        $name = $_POST['variant_name'] ?? '';
        $price = $_POST['price_override'] !== '' ? $_POST['price_override'] : null;
        $stock = $_POST['stock_quantity'] ?? 0;
        
        $image_path = null;
        if (isset($_FILES['variant_image']) && $_FILES['variant_image']['error'] == 0) {
            $filename = time() . '_' . basename($_FILES['variant_image']['name']);
            $target_file = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['variant_image']['tmp_name'], $target_file)) {
                $image_path = 'uploads/products/' . $filename;
            }
        }
        
        $productModel->addVariant($product_id, $sku, $name, $price, $stock, $image_path);
    } 
    elseif ($action === 'edit_variant') {
        $variant_id = $_POST['variant_id'];
        $sku = $_POST['variant_sku'] ?? '';
        $name = $_POST['variant_name'] ?? '';
        $price = $_POST['price_override'] !== '' ? $_POST['price_override'] : null;
        $stock = $_POST['stock_quantity'] ?? 0;
        
        $image_path = null;
        if (isset($_FILES['variant_image']) && $_FILES['variant_image']['error'] == 0) {
            $filename = time() . '_' . basename($_FILES['variant_image']['name']);
            $target_file = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['variant_image']['tmp_name'], $target_file)) {
                $image_path = 'uploads/products/' . $filename;
            }
        }
        $productModel->updateVariant($variant_id, $sku, $name, $price, $stock, $image_path);
    }
    elseif ($action === 'delete_variant') {
        $productModel->deleteVariant($_POST['variant_id']);
    }
    elseif ($action === 'add_base_image') {
        $is_primary = isset($_POST['is_primary']) ? 1 : 0;
        if (isset($_FILES['base_image']) && $_FILES['base_image']['error'] == 0) {
            $filename = time() . '_base_' . basename($_FILES['base_image']['name']);
            $target_file = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['base_image']['tmp_name'], $target_file)) {
                $image_path = 'uploads/products/' . $filename;
                $productModel->addBaseImage($product_id, $image_path, $is_primary);
            }
        }
    }
    elseif ($action === 'delete_image') {
        $productModel->deleteImage($_POST['image_id']);
    }
    
    header("Location: layout.php?page=product_manage&id=" . $product_id);
    exit();
}

$variants = $productModel->getVariants($product_id);
$base_images = $productModel->getBaseImages($product_id);

?>

<div class="mb-6">
    <a href="layout.php?page=products" class="text-sm text-pink-600 hover:text-pink-800 font-semibold uppercase tracking-widest flex items-center space-x-2 mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        <span>Back to Products</span>
    </a>
    <h2 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($product['name']); ?></h2>
    <p class="text-gray-500 mt-1 font-mono text-sm">SKU: <?php echo htmlspecialchars($product['base_sku']); ?> | Base Price: ₱<?php echo number_format($product['base_price'], 2); ?></p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 pb-12">
    
    <!-- LEFT: Base Images -->
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <h3 class="text-lg font-bold text-gray-800 mb-4 uppercase tracking-widest text-xs">Product Images</h3>
            
            <?php if (count($base_images) > 0): ?>
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <?php foreach($base_images as $img): ?>
                        <div class="relative group rounded-xl overflow-hidden border border-gray-200 aspect-square">
                            <img src="../<?php echo htmlspecialchars($img['image_url']); ?>" class="w-full h-full object-cover" alt="Product Image">
                            <?php if($img['is_primary']): ?>
                                <span class="absolute top-2 left-2 bg-pink-500 text-white text-[10px] font-bold px-2 py-1 rounded uppercase">Primary</span>
                            <?php endif; ?>
                            <form method="POST" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <input type="hidden" name="action" value="delete_image">
                                <input type="hidden" name="image_id" value="<?php echo $img['image_id']; ?>">
                                <button type="submit" class="bg-red-500 text-white p-1.5 rounded-full hover:bg-red-600 transition" onclick="return confirm('Delete image?');">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-sm text-gray-500 mb-6 bg-gray-50 p-4 rounded-xl border border-dashed border-gray-200">No generic images uploaded yet.</p>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="bg-gray-50 p-4 rounded-2xl border border-dashed border-gray-300 text-center">
                <input type="hidden" name="action" value="add_base_image">
                <input type="file" name="base_image" accept="image/*" required class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100 mb-3 cursor-pointer">
                <label class="flex items-center justify-center space-x-2 text-sm text-gray-600 mb-4 cursor-pointer">
                    <input type="checkbox" name="is_primary" class="rounded border-gray-300 text-pink-500 focus:ring-pink-400">
                    <span>Set as Primary Image</span>
                </label>
                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-pink-500 text-white py-2 rounded-full text-xs font-bold uppercase tracking-widest hover:opacity-90 transition-opacity">Upload Image</button>
            </form>
        </div>
    </div>

    <!-- RIGHT: Variants -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                <h3 class="text-lg font-bold text-gray-800 uppercase tracking-widest text-xs">Product Variants</h3>
                <button onclick="openVariantModal()" class="bg-pink-50 text-pink-600 px-4 py-2 rounded-full text-xs font-bold uppercase tracking-widest hover:bg-pink-100 transition-colors flex items-center space-x-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Add Variant</span>
                </button>
            </div>

            <!-- Variants Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white border-b border-gray-100 text-gray-400 text-[10px] uppercase tracking-widest">
                            <th class="p-5 font-semibold">Image</th>
                            <th class="p-5 font-semibold">Details</th>
                            <th class="p-5 font-semibold text-center">Price</th>
                            <th class="p-5 font-semibold text-center">Stock</th>
                            <th class="p-5 font-semibold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-700 divide-y divide-gray-50">
                        <?php if (count($variants) > 0): ?>
                            <?php foreach($variants as $v): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="p-5">
                                    <?php if($v['image_url']): ?>
                                        <img src="../<?php echo htmlspecialchars($v['image_url']); ?>" class="w-14 h-14 rounded-xl object-cover border border-gray-200 shadow-sm">
                                    <?php else: ?>
                                        <div class="w-14 h-14 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 text-[10px] font-bold uppercase tracking-widest border border-gray-200">No Img</div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-5">
                                    <p class="font-bold text-gray-900"><?php echo htmlspecialchars($v['variant_name']); ?></p>
                                    <p class="text-[10px] font-mono text-gray-500 mt-1"><?php echo htmlspecialchars($v['variant_sku']); ?></p>
                                </td>
                                <td class="p-5 text-center font-medium">
                                    <?php if ($v['price_override'] !== null): ?>
                                        <span class="text-gray-900">₱<?php echo number_format($v['price_override'], 2); ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-[10px] uppercase tracking-widest font-bold">Base Price</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-5 text-center">
                                    <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-bold"><?php echo $v['stock_quantity']; ?></span>
                                </td>
                                <td class="p-5 text-right">
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this variant?');" class="flex justify-end items-center">
                                        <button type="button" onclick='editVariant(<?php echo json_encode($v); ?>)' class="text-gray-400 hover:text-blue-500 transition-colors bg-white hover:bg-blue-50 p-2 rounded-full mr-1">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        <input type="hidden" name="action" value="delete_variant">
                                        <input type="hidden" name="variant_id" value="<?php echo $v['variant_id']; ?>">
                                        <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors bg-white hover:bg-red-50 p-2 rounded-full">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-12 text-center text-gray-400">
                                    <svg class="w-8 h-8 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                    <p class="text-sm font-medium">No variants created yet.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Variant Modal -->
<div id="variantModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl transform scale-95 transition-transform duration-300 mx-4 flex flex-col max-h-[90vh] overflow-hidden" id="variantModalContent">
        <div class="flex justify-between items-center p-8 pb-4 border-b border-gray-100 bg-white z-10 flex-shrink-0">
            <h3 class="text-xl font-bold text-gray-900 uppercase tracking-widest" id="variantModalTitle">Add New Variant</h3>
            <button type="button" onclick="closeVariantModal()" class="text-gray-400 hover:text-pink-500 transition-colors bg-gray-50 hover:bg-pink-50 p-2 rounded-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <div class="overflow-y-auto flex-1">
            <div class="p-8 pt-6">
                <form method="POST" enctype="multipart/form-data" class="space-y-6" id="variantFormEl">
                    <input type="hidden" name="action" id="variantAction" value="add_variant">
                    <input type="hidden" name="variant_id" id="modal_variant_id" value="">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-500 text-xs font-bold uppercase tracking-widest mb-2">Variant SKU *</label>
                            <input type="text" name="variant_sku" id="modal_variant_sku" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 font-mono transition-all">
                        </div>
                        <div>
                            <label class="block text-gray-500 text-xs font-bold uppercase tracking-widest mb-2">Variant Name *</label>
                            <input type="text" name="variant_name" id="modal_variant_name" required placeholder="e.g. Rose Gold, Size M" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-500 text-xs font-bold uppercase tracking-widest mb-2">Price Override (₱)</label>
                            <input type="number" step="0.01" name="price_override" id="modal_price_override" placeholder="Leave empty to use base price" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all">
                        </div>
                        <div>
                            <label class="block text-gray-500 text-xs font-bold uppercase tracking-widest mb-2">Stock Quantity *</label>
                            <input type="number" name="stock_quantity" id="modal_stock_quantity" value="0" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-500 text-xs font-bold uppercase tracking-widest mb-2">Variant Specific Image</label>
                        <input type="file" name="variant_image" accept="image/*" class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100 mb-3 cursor-pointer">
                        <p class="text-[10px] text-gray-400 mt-1">Leave empty to keep existing image if updating.</p>
                    </div>

                    <div class="pt-6 flex justify-end space-x-4">
                        <button type="button" onclick="closeVariantModal()" class="px-8 py-3 rounded-full text-gray-500 font-bold uppercase tracking-widest text-xs hover:bg-gray-100 hover:text-gray-900 transition-colors">Cancel</button>
                        <button type="submit" class="bg-gradient-to-r from-blue-600 to-pink-500 text-white px-8 py-3 rounded-full font-bold uppercase tracking-widest text-xs hover:opacity-90 shadow-xl shadow-pink-500/20 transition-opacity">Save Variant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openVariantModal() {
    const modal = document.getElementById('variantModal');
    const content = document.getElementById('variantModalContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        content.classList.remove('scale-95');
        content.classList.add('scale-100');
    }, 10);
}

function closeVariantModal() {
    const modal = document.getElementById('variantModal');
    const content = document.getElementById('variantModalContent');
    modal.classList.add('opacity-0');
    content.classList.remove('scale-100');
    content.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('variantFormEl').reset();
        document.getElementById('variantAction').value = 'add_variant';
        document.getElementById('modal_variant_id').value = '';
        document.getElementById('variantModalTitle').textContent = 'Add New Variant';
    }, 300);
}

function editVariant(v) {
    document.getElementById('variantAction').value = 'edit_variant';
    document.getElementById('modal_variant_id').value = v.variant_id;
    document.getElementById('modal_variant_sku').value = v.variant_sku;
    document.getElementById('modal_variant_name').value = v.variant_name;
    document.getElementById('modal_price_override').value = v.price_override;
    document.getElementById('modal_stock_quantity').value = v.stock_quantity;
    document.getElementById('variantModalTitle').textContent = 'Edit Variant';
    openVariantModal();
}
</script>
