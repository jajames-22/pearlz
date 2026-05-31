<?php
// admin/partials/local_order.php
require_once '../databse/database.php';
require_once '../models/Product.php';

$database = new Database();
$db = $database->getConnection();
$productModel = new Product($db);

$products = $productModel->getAll();

// We need to fetch variants for all products so the POS can handle them quickly
$allProducts = [];
foreach ($products as $p) {
    $variants = $productModel->getVariants($p['product_id']);
    $p['variants'] = $variants;
    $allProducts[] = $p;
}
?>
<div class="flex flex-col lg:flex-row h-[calc(100vh-8rem)] gap-6">
    <!-- Left Panel: Products -->
    <div class="w-full lg:w-2/3 flex flex-col bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="py-3 px-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
            <h2 class="text-xl font-bold text-gray-800 uppercase tracking-widest text-xs">Menu</h2>
            <input type="text" id="posSearch" placeholder="Search products..." class="bg-white border border-gray-200 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-pink-400 transition-all w-64" onkeyup="filterProducts()">
        </div>
        <div class="px-6 py-3 overflow-y-auto flex-1 bg-gray-50/30" id="productList">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach($allProducts as $p): ?>
                    <div class="product-card bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col cursor-pointer hover:shadow-md transition-shadow hover:border-pink-200 active:scale-95 transition-transform" 
                         data-name="<?php echo strtolower(htmlspecialchars($p['name'])); ?>"
                         onclick='openVariantModal(<?php echo json_encode($p); ?>)'>
                        <div class="h-40 w-full relative bg-gray-50 flex-shrink-0">
                            <?php if (!empty($p['thumbnail'])): ?>
                                <img src="../<?php echo htmlspecialchars($p['thumbnail']); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <svg class="w-10 h-10 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4 flex flex-col flex-grow">
                            <h3 class="font-bold text-gray-900 text-sm mb-1 leading-tight"><?php echo htmlspecialchars($p['name']); ?></h3>
                            <div class="mt-auto font-bold text-pink-600 text-sm">₱<?php echo number_format($p['base_price'], 2); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right Panel: Cart & Payment -->
    <div class="w-full lg:w-1/3 flex flex-col bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden relative">
        <div class="px-5 py-3 border-b border-gray-50 bg-gray-50/50 flex flex-col space-y-2">
            <div class="flex justify-between items-center">
                <h2 class="font-bold text-gray-800 uppercase tracking-widest text-xs">Current Order</h2>
                <button onclick="clearCart()" class="text-[10px] font-bold text-red-500 uppercase tracking-widest hover:bg-red-50 px-2 py-1 rounded-full transition-colors">Clear</button>
            </div>
            <input type="text" id="customerNameInput" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all placeholder-gray-400" placeholder="Customer Name (Optional)">
        </div>
        
        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-6" id="cartContainer">
            <div id="emptyCartMsg" class="flex flex-col items-center justify-center h-full text-gray-400">
                <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <p class="font-medium">Order is empty</p>
                <p class="text-xs mt-1">Select products to add</p>
            </div>
            <ul id="cartList" class="space-y-4 hidden">
                <!-- Cart items will go here -->
            </ul>
        </div>

        <!-- Order Summary & Payment -->
        <div class="p-4 border-t border-gray-100 bg-gray-50/30 flex-shrink-0">
            <!-- Compact Summary Row -->
            <div class="flex justify-between items-end mb-3 border-b border-gray-200 pb-2">
                <div class="text-xs text-gray-500 space-y-0.5">
                    <div>Subtotal: <span id="subtotalDisplay">₱0.00</span></div>
                    <div>Tax (12%): <span id="taxDisplay">₱0.00</span></div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Total</div>
                    <div class="font-bold text-2xl text-gray-900 leading-none" id="totalDisplay">₱0.00</div>
                </div>
            </div>

            <!-- Payment Mode Selection -->
            <div class="flex space-x-1 mb-2.5 p-1 bg-gray-200/50 rounded-lg">
                <button id="btnPayCash" onclick="setPaymentMode('cash')" class="flex-1 bg-white text-gray-900 py-1 rounded text-[10px] font-bold uppercase tracking-widest shadow-sm transition-all">Cash</button>
                <button id="btnPayOnline" onclick="setPaymentMode('online')" class="flex-1 text-gray-500 hover:text-gray-900 py-1 rounded text-[10px] font-bold uppercase tracking-widest transition-all">Online</button>
            </div>

            <!-- Numpad for Cash Input -->
            <div id="cashPaymentSection" class="mb-2">
                <div class="flex justify-between items-center mb-1.5">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Received</span>
                    <span class="text-sm font-bold text-blue-600 leading-none" id="cashInputDisplay">₱0.00</span>
                </div>
                <div class="grid grid-cols-3 gap-1">
                    <?php 
                    $keys = ['7','8','9','4','5','6','1','2','3','C','0','⌫'];
                    foreach($keys as $k): 
                        $color = ($k === 'C' || $k === '⌫') ? 'bg-gray-200 hover:bg-gray-300 text-gray-700' : 'bg-white hover:bg-blue-50 text-gray-900 border border-gray-200 hover:border-blue-300';
                    ?>
                        <button onclick="numpadInput('<?php echo $k; ?>')" class="<?php echo $color; ?> py-1.5 rounded-md font-bold text-sm shadow-sm transition-all active:scale-95"><?php echo $k; ?></button>
                    <?php endforeach; ?>
                </div>
                <div class="flex justify-between items-center text-xs font-bold text-gray-500 mt-1.5">
                    <span>Change:</span>
                    <span id="changeDisplay" class="text-green-600">₱0.00</span>
                </div>
            </div>

            <!-- Online Payment Section -->
            <div id="onlinePaymentSection" class="hidden mb-2">
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">Reference Number *</label>
                <input type="text" id="refNumberInput" oninput="updateCashDisplay()" class="w-full bg-white border border-gray-200 rounded-md px-3 py-1.5 text-xs focus:outline-none focus:border-pink-400 focus:ring-1 focus:ring-pink-400 transition-all font-mono placeholder-gray-300" placeholder="e.g. 10023456789">
            </div>

            <button onclick="processCheckout()" class="w-full bg-gradient-to-r from-blue-600 to-pink-500 text-white py-2 rounded-lg text-xs font-bold uppercase tracking-widest hover:opacity-90 shadow shadow-pink-500/30 transition-opacity active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed mt-1" id="checkoutBtn" disabled>
                Process Order
            </button>
        </div>
    </div>
</div>

<!-- Variant Selection Modal -->
<div id="variantModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white p-8 rounded-[2rem] shadow-2xl w-full max-w-md transform scale-95 transition-transform duration-300 mx-4">
        <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
            <h3 class="text-xl font-bold text-gray-900" id="vModalTitle">Select Variant</h3>
            <button onclick="closeVariantModal()" class="text-gray-400 hover:text-pink-500 bg-gray-50 p-2 rounded-full transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div id="variantList" class="space-y-3 max-h-[60vh] overflow-y-auto pr-2">
            <!-- Variant options go here -->
        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    #receiptModal, #receiptModal * { visibility: visible; }
    #receiptModal { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; background: white; }
    .no-print { display: none !important; }
}
</style>

<!-- Receipt Modal -->
<div id="receiptModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white p-8 w-full max-w-sm mx-4 flex flex-col relative rounded-xl shadow-2xl font-mono">
        <div class="text-center mb-4">
            <h2 class="text-2xl font-bold">PEARLZ</h2>
            <p class="text-xs text-gray-600">Premium Jewelry</p>
            <p class="text-xs mt-2" id="receiptDate"></p>
            <p class="text-xs mt-1 font-bold hidden" id="receiptCustomerName"></p>
        </div>
        <div class="border-t border-dashed border-gray-400 my-3"></div>
        <div id="receiptItems" class="text-sm space-y-2 my-3"></div>
        <div class="border-t border-dashed border-gray-400 my-3"></div>
        <div class="flex justify-between text-sm mb-1">
            <span>Subtotal:</span><span id="receiptSubtotal"></span>
        </div>
        <div class="flex justify-between text-sm mb-1">
            <span>Tax (12%):</span><span id="receiptTax"></span>
        </div>
        <div class="flex justify-between font-bold text-lg mt-2">
            <span>Total:</span><span id="receiptTotal"></span>
        </div>
        <div class="border-t border-dashed border-gray-400 my-3"></div>
        <div class="flex justify-between text-sm mb-1">
            <span id="receiptPayMethodLabel">Cash:</span><span id="receiptTendered"></span>
        </div>
        <div class="flex justify-between text-sm">
            <span>Change:</span><span id="receiptChange"></span>
        </div>
        <div class="text-center mt-8 text-xs text-gray-500">
            <p>Thank you for shopping!</p>
        </div>
        
        <div class="mt-8 flex space-x-3 no-print">
            <button onclick="closeReceipt()" class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-lg font-sans text-xs font-bold uppercase tracking-widest hover:bg-gray-200 transition-colors">Close</button>
            <button onclick="window.print()" class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-sans text-xs font-bold uppercase tracking-widest hover:bg-blue-700 transition-colors">Print</button>
        </div>
    </div>
</div>

<script>
let paymentMode = 'cash'; // 'cash' or 'online'
let cart = [];
let cashInput = "";
let currentTotal = 0;

function filterProducts() {
    const q = document.getElementById('posSearch').value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(card => {
        if(card.dataset.name.includes(q)) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
}

function openVariantModal(product) {
    if(!product.variants || product.variants.length === 0) {
        alert('This product has no variants/stock available yet. Please add variants first in Product Management.');
        return;
    }
    
    // Automatically add if there is exactly 1 variant
    if(product.variants.length === 1) {
        addToCart(product, product.variants[0]);
        return;
    }

    document.getElementById('vModalTitle').textContent = product.name;
    const vList = document.getElementById('variantList');
    vList.innerHTML = '';
    
    product.variants.forEach(v => {
        const price = v.price_override !== null ? parseFloat(v.price_override) : parseFloat(product.base_price);
        const div = document.createElement('div');
        const isOutOfStock = v.stock_quantity <= 0;
        
        div.className = `flex justify-between items-center p-4 rounded-xl border ${isOutOfStock ? 'bg-gray-50 border-gray-100 opacity-50' : 'bg-white border-gray-200 hover:border-pink-300 cursor-pointer hover:shadow-sm transition-all active:scale-[0.98]'}`;
        
        div.innerHTML = `
            <div>
                <div class="font-bold text-gray-900">${v.variant_name}</div>
                <div class="text-xs text-gray-500 font-mono mt-1">${v.variant_sku} | Stock: ${v.stock_quantity}</div>
            </div>
            <div class="font-bold text-pink-600">₱${price.toFixed(2)}</div>
        `;
        
        if(!isOutOfStock) {
            div.onclick = () => {
                addToCart(product, v);
                closeVariantModal();
            };
        }
        vList.appendChild(div);
    });

    const modal = document.getElementById('variantModal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modal.children[0].classList.remove('scale-95');
        modal.children[0].classList.add('scale-100');
    }, 10);
}

function closeVariantModal() {
    const modal = document.getElementById('variantModal');
    modal.classList.add('opacity-0');
    modal.children[0].classList.remove('scale-100');
    modal.children[0].classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

function addToCart(product, variant) {
    const cartItemId = variant.variant_id;
    const existing = cart.find(i => i.variant_id === cartItemId);
    const price = variant.price_override !== null ? parseFloat(variant.price_override) : parseFloat(product.base_price);
    
    if (existing) {
        if(existing.qty >= variant.stock_quantity) {
            alert('Cannot exceed available stock!');
            return;
        }
        existing.qty++;
    } else {
        if(variant.stock_quantity <= 0) {
            alert('Out of stock!');
            return;
        }
        cart.push({
            variant_id: cartItemId,
            product_id: product.product_id,
            name: product.name,
            variant_name: variant.variant_name,
            price: price,
            qty: 1,
            max_stock: variant.stock_quantity
        });
    }
    updateCartUI();
}

function removeFromCart(variant_id) {
    cart = cart.filter(i => i.variant_id !== variant_id);
    updateCartUI();
}

function changeQty(variant_id, delta) {
    const item = cart.find(i => i.variant_id === variant_id);
    if(item) {
        const newQty = item.qty + delta;
        if(newQty > 0 && newQty <= item.max_stock) {
            item.qty = newQty;
        } else if (newQty === 0) {
            removeFromCart(variant_id);
            return;
        }
        updateCartUI();
    }
}

function clearCart() {
    cart = [];
    cashInput = "";
    updateCartUI();
}

function updateCartUI() {
    const container = document.getElementById('cartList');
    const emptyMsg = document.getElementById('emptyCartMsg');
    const checkoutBtn = document.getElementById('checkoutBtn');
    
    container.innerHTML = '';
    
    if(cart.length === 0) {
        container.classList.add('hidden');
        emptyMsg.classList.remove('hidden');
        checkoutBtn.disabled = true;
        currentTotal = 0;
    } else {
        container.classList.remove('hidden');
        emptyMsg.classList.add('hidden');
        checkoutBtn.disabled = false;
        
        let total = 0;
        cart.forEach(item => {
            const itemTotal = item.price * item.qty;
            total += itemTotal;
            
            const li = document.createElement('li');
            li.className = 'flex justify-between items-center bg-white border border-gray-100 p-3 rounded-xl shadow-sm';
            li.innerHTML = `
                <div class="flex-1">
                    <div class="font-bold text-sm text-gray-900 leading-tight">${item.name}</div>
                    <div class="text-[10px] text-gray-500 uppercase tracking-widest font-bold mt-1">${item.variant_name}</div>
                    <div class="text-xs font-medium text-pink-600 mt-1">₱${item.price.toFixed(2)}</div>
                </div>
                <div class="flex items-center space-x-2 bg-gray-50 rounded-lg border border-gray-200 p-1">
                    <button onclick="changeQty(${item.variant_id}, -1)" class="w-6 h-6 flex items-center justify-center bg-white rounded text-gray-600 hover:text-red-500 shadow-sm transition-colors">-</button>
                    <span class="text-xs font-bold w-6 text-center">${item.qty}</span>
                    <button onclick="changeQty(${item.variant_id}, 1)" class="w-6 h-6 flex items-center justify-center bg-white rounded text-gray-600 hover:text-blue-500 shadow-sm transition-colors">+</button>
                </div>
                <div class="ml-4 font-bold text-gray-900 w-16 text-right text-sm">
                    ₱${itemTotal.toFixed(2)}
                </div>
                <button onclick="removeFromCart(${item.variant_id})" class="ml-2 text-gray-300 hover:text-red-500 transition-colors p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            `;
            container.appendChild(li);
        });
        currentTotal = total;
    }
    
    // Calculate subtotal and tax assuming total is VAT inclusive
    const subtotal = currentTotal / 1.12;
    const tax = currentTotal - subtotal;
    
    document.getElementById('subtotalDisplay').textContent = `₱${subtotal.toFixed(2)}`;
    document.getElementById('taxDisplay').textContent = `₱${tax.toFixed(2)}`;
    document.getElementById('totalDisplay').textContent = `₱${currentTotal.toFixed(2)}`;
    
    updateCashDisplay();
}

function numpadInput(key) {
    if(key === 'C') {
        cashInput = "";
    } else if (key === '⌫') {
        cashInput = cashInput.slice(0, -1);
    } else {
        if(cashInput.length < 8) {
            cashInput += key;
        }
    }
    updateCashDisplay();
}

function updateCashDisplay() {
    let cashVal = parseFloat(cashInput);
    if(isNaN(cashVal)) cashVal = 0;
    
    document.getElementById('cashInputDisplay').textContent = `₱${cashVal.toFixed(2)}`;
    
    const changeDisplay = document.getElementById('changeDisplay');
    const checkoutBtn = document.getElementById('checkoutBtn');
    
    if(cart.length > 0) {
        if(paymentMode === 'online') {
            const refNo = document.getElementById('refNumberInput').value.trim();
            checkoutBtn.disabled = refNo.length === 0;
        } else {
            if(cashVal >= currentTotal) {
                const change = cashVal - currentTotal;
                changeDisplay.textContent = `₱${change.toFixed(2)}`;
                changeDisplay.classList.remove('text-red-500');
                changeDisplay.classList.add('text-green-600');
                checkoutBtn.disabled = false;
            } else {
                const short = currentTotal - cashVal;
                changeDisplay.textContent = `Short: ₱${short.toFixed(2)}`;
                changeDisplay.classList.remove('text-green-600');
                changeDisplay.classList.add('text-red-500');
                checkoutBtn.disabled = true;
            }
        }
    } else {
        if(paymentMode === 'cash') {
            changeDisplay.textContent = `₱0.00`;
            changeDisplay.classList.remove('text-red-500');
            changeDisplay.classList.add('text-green-600');
        }
        checkoutBtn.disabled = true;
    }
}

function processCheckout() {
    if(cart.length === 0 || document.getElementById('checkoutBtn').disabled) return;
    
    const cashVal = parseFloat(cashInput) || 0;
    const change = paymentMode === 'cash' ? cashVal - currentTotal : 0;
    const refNo = document.getElementById('refNumberInput').value.trim();
    const customerName = document.getElementById('customerNameInput').value.trim();
    
    document.getElementById('receiptDate').textContent = new Date().toLocaleString();
    
    const customerEl = document.getElementById('receiptCustomerName');
    if(customerName) {
        customerEl.textContent = `Customer: ${customerName}`;
        customerEl.classList.remove('hidden');
    } else {
        customerEl.classList.add('hidden');
    }
    
    const itemsContainer = document.getElementById('receiptItems');
    itemsContainer.innerHTML = '';
    
    cart.forEach(item => {
        const div = document.createElement('div');
        div.className = 'flex justify-between';
        div.innerHTML = `<span>${item.qty}x ${item.name}</span><span>₱${(item.price * item.qty).toFixed(2)}</span>`;
        itemsContainer.appendChild(div);
        
        if(item.variant) {
            const vDiv = document.createElement('div');
            vDiv.className = 'text-[10px] text-gray-500 ml-4 mb-1';
            vDiv.textContent = `Var: ${item.variant}`;
            itemsContainer.appendChild(vDiv);
        }
    });
    
    const subtotal = currentTotal / 1.12;
    const tax = currentTotal - subtotal;
    document.getElementById('receiptSubtotal').textContent = `₱${subtotal.toFixed(2)}`;
    document.getElementById('receiptTax').textContent = `₱${tax.toFixed(2)}`;
    document.getElementById('receiptTotal').textContent = `₱${currentTotal.toFixed(2)}`;
    
    if(paymentMode === 'cash') {
        document.getElementById('receiptPayMethodLabel').textContent = 'Cash:';
        document.getElementById('receiptTendered').textContent = `₱${cashVal.toFixed(2)}`;
        document.getElementById('receiptChange').textContent = `₱${change.toFixed(2)}`;
    } else {
        document.getElementById('receiptPayMethodLabel').textContent = 'GCash Ref:';
        document.getElementById('receiptTendered').textContent = refNo;
        document.getElementById('receiptChange').textContent = `₱0.00`;
    }
    
    const modal = document.getElementById('receiptModal');
    modal.classList.remove('hidden');
    setTimeout(() => modal.classList.remove('opacity-0'), 10);
    
    clearCart();
    document.getElementById('refNumberInput').value = '';
    document.getElementById('customerNameInput').value = '';
    cashInput = '';
    updateCashDisplay();
}

function closeReceipt() {
    const modal = document.getElementById('receiptModal');
    modal.classList.add('opacity-0');
    setTimeout(() => modal.classList.add('hidden'), 300);
}

function setPaymentMode(mode) {
    paymentMode = mode;
    const btnCash = document.getElementById('btnPayCash');
    const btnOnline = document.getElementById('btnPayOnline');
    const secCash = document.getElementById('cashPaymentSection');
    const secOnline = document.getElementById('onlinePaymentSection');
    
    if(mode === 'cash') {
        btnCash.className = "flex-1 bg-white text-gray-900 py-1.5 rounded-lg text-xs font-bold uppercase tracking-widest shadow-sm transition-all";
        btnOnline.className = "flex-1 text-gray-500 hover:text-gray-900 py-1.5 rounded-lg text-xs font-bold uppercase tracking-widest transition-all";
        secCash.classList.remove('hidden');
        secOnline.classList.add('hidden');
    } else {
        btnOnline.className = "flex-1 bg-white text-gray-900 py-1.5 rounded-lg text-xs font-bold uppercase tracking-widest shadow-sm transition-all";
        btnCash.className = "flex-1 text-gray-500 hover:text-gray-900 py-1.5 rounded-lg text-xs font-bold uppercase tracking-widest transition-all";
        secOnline.classList.remove('hidden');
        secCash.classList.add('hidden');
    }
    updateCashDisplay();
}

document.addEventListener('keydown', (e) => {
    if(['posSearch', 'refNumberInput', 'customerNameInput'].includes(document.activeElement.id)) {
        if (e.key === 'Enter' && document.activeElement.id === 'refNumberInput') {
             const checkoutBtn = document.getElementById('checkoutBtn');
             if(!checkoutBtn.disabled) processCheckout();
        }
        return;
    }

    if(paymentMode === 'cash') {
        const key = e.key;
        if(key >= '0' && key <= '9') {
            numpadInput(key);
        } else if (key === 'Backspace') {
            numpadInput('⌫');
        } else if (key === 'Escape' || key.toLowerCase() === 'c') {
            numpadInput('C');
        }
    }
    
    if (e.key === 'Enter') {
        const checkoutBtn = document.getElementById('checkoutBtn');
        if(!checkoutBtn.disabled) {
            processCheckout();
        }
    }
});
</script>
