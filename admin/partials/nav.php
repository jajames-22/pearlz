<aside id="adminSidebar" class="w-64 bg-gray-900 text-white flex flex-col shadow-2xl fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out z-40">
    <div class="p-6 border-b border-gray-800 flex justify-between md:justify-center items-center h-20">
        <img src="../images/logo.png" alt="Pearlz Logo" class="h-10 ">
        <button onclick="toggleSidebar()" class="md:hidden text-gray-400 hover:text-white focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    
    <nav class="flex-1 py-8 flex flex-col space-y-2 px-4">
        <a href="layout.php?page=local_order" class="px-4 py-3 text-sm font-medium rounded-xl hover:bg-gray-800 transition-colors flex items-center space-x-3 <?php echo (!isset($_GET['page']) || $_GET['page'] == 'local_order') ? 'bg-gray-800 text-pink-400' : 'text-gray-300 hover:text-white'; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            <span>Local Order</span>
        </a>
        
        <a href="layout.php?page=online_orders" class="px-4 py-3 text-sm font-medium rounded-xl hover:bg-gray-800 transition-colors flex items-center space-x-3 <?php echo (isset($_GET['page']) && $_GET['page'] == 'online_orders') ? 'bg-gray-800 text-pink-400' : 'text-gray-300 hover:text-white'; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            <span>Online Orders</span>
        </a>
        
        <a href="layout.php?page=products" class="px-4 py-3 text-sm font-medium rounded-xl hover:bg-gray-800 transition-colors flex items-center space-x-3 <?php echo (isset($_GET['page']) && $_GET['page'] == 'products') ? 'bg-gray-800 text-pink-400' : 'text-gray-300 hover:text-white'; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            <span>Products</span>
        </a>
        
        <a href="layout.php?page=customers" class="px-4 py-3 text-sm font-medium rounded-xl hover:bg-gray-800 transition-colors flex items-center space-x-3 <?php echo (isset($_GET['page']) && $_GET['page'] == 'customers') ? 'bg-gray-800 text-pink-400' : 'text-gray-300 hover:text-white'; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <span>Customers</span>
        </a>
        
        <a href="layout.php?page=analytics" class="px-4 py-3 text-sm font-medium rounded-xl hover:bg-gray-800 transition-colors flex items-center space-x-3 <?php echo (isset($_GET['page']) && $_GET['page'] == 'analytics') ? 'bg-gray-800 text-pink-400' : 'text-gray-300 hover:text-white'; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            <span>Analytics</span>
        </a>
        
        <a href="layout.php?page=vouchers" class="px-4 py-3 text-sm font-medium rounded-xl hover:bg-gray-800 transition-colors flex items-center space-x-3 <?php echo (isset($_GET['page']) && $_GET['page'] == 'vouchers') ? 'bg-gray-800 text-pink-400' : 'text-gray-300 hover:text-white'; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
            <span>Vouchers</span>
        </a>
    </nav>
    
    <div class="p-4 border-t border-gray-800">
        <a href="#" onclick="openLogoutModal()" class="flex items-center space-x-3 text-sm font-medium text-gray-400 hover:text-pink-500 transition-colors px-4 py-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            <span>Log Out</span>
        </a>
    </div>
</aside>
