<?php
// index.php
// Placeholder for future database connection:
// require_once 'config/database.php';
// $db = new Database(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pearlz | Exclusive Customized Elegance</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Amsterdam One';
            font-style: normal;
            font-weight: 400;
            src: local('Amsterdam One'), local('Amsterdam'), url('https://fonts.cdnfonts.com/s/17992/Amsterdam.woff') format('woff');
        }
        /* Custom font utility for the logo and headings */
        .font-logo { font-family: 'Amsterdam One', cursive; font-weight: normal; }
        body { font-family: 'DM Sans', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-100 via-blue-50 to-pink-100 min-h-screen text-gray-800 antialiased">

    <header class="py-6 px-8 flex items-center justify-between bg-white/40 backdrop-blur-md sticky top-0 z-50 border-b border-white/50">
        
        <div class="flex-1 hidden md:flex space-x-6 text-sm uppercase tracking-widest font-semibold">
            <a href="#" class="hover:text-pink-500 transition-colors">Shop</a>
            <a href="#" class="hover:text-pink-500 transition-colors">Collections</a>
        </div>

        <div class="flex-1 flex justify-center">
            <a href="index.php" class="text-5xl font-logo tracking-widest text-gray-900" style="text-transform: none;">
                Pearlz
            </a>
        </div>

        <div class="flex-1 flex justify-end space-x-6 text-sm uppercase tracking-widest font-semibold">
            <a href="#" class="hover:text-pink-500 transition-colors">Account</a>
            <a href="#" class="hover:text-pink-500 transition-colors">Cart (0)</a>
        </div>
        
        <div class="md:hidden flex-1 flex justify-end">
            <button class="text-gray-900 focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        
        <section class="text-center py-24">
            <h1 class="text-5xl md:text-7xl font-bold mb-6 text-gray-900">Handmade warmth, pearlescent charm.</h1>
            <p class="text-lg md:text-xl max-w-2xl mx-auto mb-10 text-gray-700 leading-relaxed">
                Discover our exclusive collections of limited-edition jewelry. Select your variant and make it yours with personalized engravings.
            </p>
            <a href="#featured" class="inline-block bg-gray-900 text-white px-10 py-4 uppercase tracking-widest text-sm hover:bg-gray-800 transition shadow-xl rounded-sm">
                Shop the Collection
            </a>
        </section>

        <section id="featured" class="mt-16">
            <div class="flex justify-between items-end mb-10">
                <h2 class="text-3xl font-bold text-gray-900">Featured Exclusives</h2>
                <a href="#" class="text-sm font-semibold uppercase tracking-wider text-pink-600 hover:text-pink-800 transition">View All &rarr;</a>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-10">
                
                <div class="group cursor-pointer">
                    <div class="h-80 bg-white/60 backdrop-blur-sm rounded-lg mb-4 flex items-center justify-center text-gray-400 overflow-hidden shadow-sm group-hover:shadow-md transition duration-300">
                        <span class="text-sm uppercase tracking-widest">[Feature Image]</span>
                    </div>
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-lg text-gray-900 group-hover:text-pink-600 transition">Signature Cuff</h3>
                            <p class="text-gray-500 text-sm mt-1">Custom Engraving Available</p>
                        </div>
                        <p class="font-semibold text-gray-900">$250.00</p>
                    </div>
                </div>

                <div class="group cursor-pointer">
                    <div class="h-80 bg-white/60 backdrop-blur-sm rounded-lg mb-4 flex items-center justify-center text-gray-400 overflow-hidden shadow-sm group-hover:shadow-md transition duration-300 relative">
                        <span class="absolute top-4 left-4 bg-gray-900 text-white text-xs px-2 py-1 uppercase tracking-wider rounded-sm">Limited</span>
                        <span class="text-sm uppercase tracking-widest">[Feature Image]</span>
                    </div>
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-lg text-gray-900 group-hover:text-pink-600 transition">Rose Gold Timepiece</h3>
                            <p class="text-gray-500 text-sm mt-1">3 Variants</p>
                        </div>
                        <p class="font-semibold text-gray-900">$1,200.00</p>
                    </div>
                </div>

                <div class="group cursor-pointer">
                    <div class="h-80 bg-white/60 backdrop-blur-sm rounded-lg mb-4 flex items-center justify-center text-gray-400 overflow-hidden shadow-sm group-hover:shadow-md transition duration-300">
                        <span class="text-sm uppercase tracking-widest">[Feature Image]</span>
                    </div>
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-lg text-gray-900 group-hover:text-pink-600 transition">Pearl Drop Earrings</h3>
                            <p class="text-gray-500 text-sm mt-1">Silver / Gold</p>
                        </div>
                        <p class="font-semibold text-gray-900">$185.00</p>
                    </div>
                </div>

            </div>
        </section>
    </main>

    <footer class="bg-white/30 backdrop-blur-md border-t border-white/50 mt-24 py-10">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-500 text-sm">&copy; <?php echo date("Y"); ?> Pearlz Exclusive. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>