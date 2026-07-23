<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EcommerceSeeder extends Seeder
{
    public function run(): void
    {
        // Clean up soft-deleted records to prevent unique constraint conflicts
        Category::withTrashed()->whereNotNull('deleted_at')->forceDelete();
        Product::withTrashed()->whereNotNull('deleted_at')->forceDelete();
        Brand::withTrashed()->whereNotNull('deleted_at')->forceDelete();
        // Units
        $units = [];
        foreach (['Piece', 'Box', 'Kg', 'Set', 'Pair', 'Liter', 'Meter', 'Pack'] as $u) {
            $units[$u] = Unit::updateOrCreate(['name' => $u], ['status' => 'active'])->id;
        }

        // Categories (top-level)
        $catData = [
            'Electronics' => ['Phones', 'Laptops', 'Accessories', 'Audio', 'Wearables'],
            'Fashion' => ['Men', 'Women', 'Kids', 'Shoes', 'Bags'],
            'Home & Living' => ['Furniture', 'Kitchen', 'Decor', 'Lighting'],
            'Sports' => ['Fitness', 'Outdoor', 'Team Sports', 'Cycling'],
            'Books' => ['Fiction', 'Non-Fiction', 'Academic', 'Comics'],
            'Beauty' => ['Skincare', 'Makeup', 'Haircare', 'Fragrances'],
            'Toys & Games' => ['Action Figures', 'Board Games', 'LEGO', 'Puzzles'],
            'Food & Beverages' => ['Snacks', 'Beverages', 'Organic', 'Coffee'],
        ];

        $categories = [];
        foreach ($catData as $parentName => $children) {
            $parent = Category::updateOrCreate(
                ['url_slug' => Str::slug($parentName)],
                ['name' => $parentName, 'status' => 'active']
            );
            $categories[$parentName] = $parent->id;
            foreach ($children as $childName) {
                $child = Category::updateOrCreate(
                    ['url_slug' => Str::slug($childName), 'fk_category_id' => $parent->id],
                    ['name' => $childName, 'status' => 'active']
                );
                $categories[$childName] = $child->id;
            }
        }

        // Brands
        $brandNames = [
            'Samsung', 'Apple', 'Nike', 'Adidas', 'Sony', 'LG', 'Ikea', 'Logitech',
            'HP', 'Dell', 'Boat', 'JBL', 'Levis', 'H&M', 'Puma', 'Canon', 'Casio',
            'Parker', 'Faber-Castell', 'LEGO', 'Hasbro', 'Nestle', 'Starbucks',
            'L\'Oreal', 'Maybelline', 'The Body Shop', 'Decathlon', 'Under Armour',
            'Ray-Ban', 'Fossil', 'Titan', 'Bose', 'Philips', 'KitchenAid', 'Google',
        ];
        $brands = [];
        foreach ($brandNames as $b) {
            $brands[$b] = Brand::updateOrCreate(
                ['url_slug' => Str::slug($b)],
                ['name' => $b, 'status' => 'active']
            )->id;
        }

        // Suppliers as vendors
        $suppliers = Supplier::where('status', 'active')->get();
        if ($suppliers->count() < 2) {
            $this->command?->error('Need at least 2 suppliers. Run SupplierSeeder first.');
            return;
        }

        // ── Products with Unsplash image queries ──
        $products = [
            // ── ELECTRONICS: Phones ──
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'cat' => 'Phones', 'brand' => 'Samsung',
                'price' => 1299.99, 'buy' => 950.00, 'qty' => 45,
                'sku' => 'SAM-S24U',
                'desc' => 'Premium smartphone with S Pen, 200MP camera, and titanium frame. AI-powered photography.',
                'image_query' => 'samsung+galaxy+phone',
            ],
            [
                'name' => 'iPhone 15 Pro Max',
                'cat' => 'Phones', 'brand' => 'Apple',
                'price' => 1199.99, 'buy' => 880.00, 'qty' => 38,
                'sku' => 'APL-15PM',
                'desc' => 'Apple flagship with A17 Pro chip, titanium design, and 5x optical zoom.',
                'image_query' => 'iphone+pro+max',
            ],
            [
                'name' => 'Google Pixel 8 Pro',
                'cat' => 'Phones', 'brand' => 'Google',
                'price' => 999.99, 'buy' => 650.00, 'qty' => 30,
                'sku' => 'GGL-P8P',
                'desc' => 'Google AI-powered phone with Tensor G3, best-in-class camera, 7 years of updates.',
                'image_query' => 'google+pixel+phone',
            ],

            // ── ELECTRONICS: Laptops ──
            [
                'name' => 'MacBook Pro 16" M3',
                'cat' => 'Laptops', 'brand' => 'Apple',
                'price' => 2499.99, 'buy' => 1850.00, 'qty' => 20,
                'sku' => 'APL-MBP16',
                'desc' => 'Powerhouse laptop with M3 Pro chip, 18GB RAM, 512GB SSD. Liquid Retina XDR display.',
                'image_query' => 'macbook+pro+laptop',
            ],
            [
                'name' => 'Dell XPS 15',
                'cat' => 'Laptops', 'brand' => 'Dell',
                'price' => 1799.99, 'buy' => 1300.00, 'qty' => 25,
                'sku' => 'DEL-XPS15',
                'desc' => 'OLED InfinityEdge display, Intel i9, 32GB RAM, 1TB SSD. Perfect for creators.',
                'image_query' => 'dell+laptop+xps',
            ],
            [
                'name' => 'HP Pavilion 14"',
                'cat' => 'Laptops', 'brand' => 'HP',
                'price' => 799.99, 'buy' => 520.00, 'qty' => 35,
                'sku' => 'HP-PAV14',
                'desc' => 'Slim laptop with 13th gen Intel i5, 16GB RAM, 512GB SSD. FHD IPS display.',
                'image_query' => 'hp+laptop+pavilion',
            ],

            // ── ELECTRONICS: Audio ──
            [
                'name' => 'Sony WH-1000XM5',
                'cat' => 'Audio', 'brand' => 'Sony',
                'price' => 349.99, 'buy' => 220.00, 'qty' => 60,
                'sku' => 'SNY-WH1000',
                'desc' => 'Industry-leading noise canceling headphones. 30-hour battery, multipoint connection.',
                'image_query' => 'sony+headphones+wireless',
            ],
            [
                'name' => 'JBL Charge 5',
                'cat' => 'Audio', 'brand' => 'JBL',
                'price' => 179.99, 'buy' => 110.00, 'qty' => 80,
                'sku' => 'JBL-CHG5',
                'desc' => 'Portable Bluetooth speaker with powerful bass, IP67 waterproof, 20-hour playtime.',
                'image_query' => 'jbl+bluetooth+speaker',
            ],
            [
                'name' => 'Boat Rockerz 550',
                'cat' => 'Audio', 'brand' => 'Boat',
                'price' => 59.99, 'buy' => 25.00, 'qty' => 180,
                'sku' => 'BOT-R550',
                'desc' => 'Over-ear wireless headphones with 50mm drivers, 20-hour playback.',
                'image_query' => 'wireless+headphones+over+ear',
            ],

            // ── ELECTRONICS: Accessories ──
            [
                'name' => 'Logitech MX Master 3S',
                'cat' => 'Accessories', 'brand' => 'Logitech',
                'price' => 99.99, 'buy' => 55.00, 'qty' => 150,
                'sku' => 'LOG-MXM3S',
                'desc' => 'Wireless mouse with MagSpeed scroll wheel, 8K DPI sensor, USB-C charging.',
                'image_query' => 'logitech+mx+master+mouse',
            ],
            [
                'name' => 'Samsung 65" OLED 4K TV',
                'cat' => 'Accessories', 'brand' => 'Samsung',
                'price' => 1899.99, 'buy' => 1350.00, 'qty' => 15,
                'sku' => 'SAM-OLED65',
                'desc' => 'Stunning OLED display with infinite contrast, Dolby Atmos, Tizen smart TV.',
                'image_query' => 'samsung+oled+tv+4k',
            ],
            [
                'name' => 'LG OLED C3 55"',
                'cat' => 'Accessories', 'brand' => 'LG',
                'price' => 1299.99, 'buy' => 900.00, 'qty' => 22,
                'sku' => 'LG-OLED55',
                'desc' => 'OLED evo panel, α9 Gen6 AI Processor, Dolby Vision & Atmos, webOS 23.',
                'image_query' => 'lg+oled+television',
            ],

            // ── ELECTRONICS: Wearables ──
            [
                'name' => 'Apple Watch Series 9',
                'cat' => 'Wearables', 'brand' => 'Apple',
                'price' => 449.99, 'buy' => 300.00, 'qty' => 55,
                'sku' => 'APL-AW9',
                'desc' => 'Advanced health features, S9 chip, always-on display, carbon neutral.',
                'image_query' => 'apple+watch+series',
            ],
            [
                'name' => 'Casio G-Shock GA-2100',
                'cat' => 'Wearables', 'brand' => 'Casio',
                'price' => 129.99, 'buy' => 65.00, 'qty' => 90,
                'sku' => 'CAS-GA2100',
                'desc' => 'Iconic CasiOak design, 200m water resistance, world time, LED light.',
                'image_query' => 'casio+gshock+watch',
            ],

            // ── FASHION: Shoes ──
            [
                'name' => 'Nike Air Max 270',
                'cat' => 'Shoes', 'brand' => 'Nike',
                'price' => 159.99, 'buy' => 85.00, 'qty' => 120,
                'sku' => 'NKE-AM270',
                'desc' => 'Iconic lifestyle sneaker with Max Air unit for all-day comfort.',
                'image_query' => 'nike+air+max+sneaker',
            ],
            [
                'name' => 'Adidas Ultraboost 23',
                'cat' => 'Shoes', 'brand' => 'Adidas',
                'price' => 189.99, 'buy' => 100.00, 'qty' => 95,
                'sku' => 'ADI-UB23',
                'desc' => 'Responsive running shoe with BOOST midsole and Continental rubber outsole.',
                'image_query' => 'adidas+ultraboost+running',
            ],
            [
                'name' => 'Puma RS-X Reinvention',
                'cat' => 'Shoes', 'brand' => 'Puma',
                'price' => 109.99, 'buy' => 55.00, 'qty' => 70,
                'sku' => 'PUM-RSX',
                'desc' => 'Retro-inspired sneaker with bold colorway and RS foam cushioning.',
                'image_query' => 'puma+rs+x+sneaker',
            ],

            // ── FASHION: Men ──
            [
                'name' => "Levi's 501 Original Jeans",
                'cat' => 'Men', 'brand' => 'Levis',
                'price' => 89.99, 'buy' => 35.00, 'qty' => 200,
                'sku' => 'LEV-501',
                'desc' => 'The original straight leg jean since 1873. Button fly, authentic fit.',
                'image_query' => 'levis+501+jeans+denim',
            ],

            // ── FASHION: Women ──
            [
                'name' => 'H&M Oversized T-Shirt',
                'cat' => 'Women', 'brand' => 'H&M',
                'price' => 24.99, 'buy' => 8.00, 'qty' => 300,
                'sku' => 'HM-OVT',
                'desc' => 'Relaxed-fit cotton t-shirt. Soft jersey fabric, round neckline.',
                'image_query' => 'h%26m+women+tshirt+cotton',
            ],

            // ── FASHION: Bags ──
            [
                'name' => 'Fossil Vintage Leather Messenger',
                'cat' => 'Bags', 'brand' => 'Fossil',
                'price' => 199.99, 'buy' => 90.00, 'qty' => 40,
                'sku' => 'FOS-VMES',
                'desc' => 'Full-grain leather messenger bag with adjustable strap and brass hardware.',
                'image_query' => 'fossil+leather+bag+messenger',
            ],
            [
                'name' => 'Ray-Ban Aviator Classic',
                'cat' => 'Accessories', 'brand' => 'Ray-Ban',
                'price' => 163.00, 'buy' => 80.00, 'qty' => 65,
                'sku' => 'RB-AVI',
                'desc' => 'Iconic aviator sunglasses. Green lens, gold frame. UV protection.',
                'image_query' => 'ray+ban+aviator+sunglasses',
            ],

            // ── HOME: Kitchen ──
            [
                'name' => 'KitchenAid Artisan Stand Mixer',
                'cat' => 'Kitchen', 'brand' => 'KitchenAid',
                'price' => 449.99, 'buy' => 280.00, 'qty' => 25,
                'sku' => 'KCH-ART5',
                'desc' => '5-quart tilt-head stand mixer. 10 speeds, includes flat beater, dough hook, wire whip.',
                'image_query' => 'kitchenaid+stand+mixer',
            ],
            [
                'name' => 'Philips Air Fryer XXL',
                'cat' => 'Kitchen', 'brand' => 'Philips',
                'price' => 249.99, 'buy' => 150.00, 'qty' => 35,
                'sku' => 'PHI-AFXXL',
                'desc' => 'Premium air fryer with Rapid Air Technology. Cook with up to 90% less fat.',
                'image_query' => 'philips+air+fryer',
            ],

            // ── HOME: Furniture ──
            [
                'name' => 'Ikea KALLAX Shelf Unit',
                'cat' => 'Furniture', 'brand' => 'Ikea',
                'price' => 89.99, 'buy' => 45.00, 'qty' => 50,
                'sku' => 'IK-KALLAX',
                'desc' => 'Versatile shelf unit, 4x2 cubes. Perfect as room divider or storage.',
                'image_query' => 'ikea+kallax+shelf+storage',
            ],

            // ── SPORTS: Fitness ──
            [
                'name' => 'Decathlon Dumbbell Set 20kg',
                'cat' => 'Fitness', 'brand' => 'Decathlon',
                'price' => 79.99, 'buy' => 35.00, 'qty' => 45,
                'sku' => 'DEC-DB20',
                'desc' => 'Adjustable dumbbell set with carrying case. Vinyl-coated iron plates.',
                'image_query' => 'dumbbell+set+fitness+weights',
            ],
            [
                'name' => 'Under Armour Tech T-Shirt',
                'cat' => 'Fitness', 'brand' => 'Under Armour',
                'price' => 34.99, 'buy' => 12.00, 'qty' => 150,
                'sku' => 'UA-TECH',
                'desc' => 'Lightweight, moisture-wicking training tee. Anti-odor technology.',
                'image_query' => 'under+armour+training+shirt',
            ],

            // ── BOOKS ──
            [
                'name' => 'Atomic Habits by James Clear',
                'cat' => 'Non-Fiction', 'brand' => 'Parker',
                'price' => 16.99, 'buy' => 8.00, 'qty' => 100,
                'sku' => 'BK-ATHAB',
                'desc' => 'Tiny Changes, Remarkable Results. The #1 New York Times bestseller.',
                'image_query' => 'atomic+habits+book',
            ],
            [
                'name' => 'The Alchemist by Paulo Coelho',
                'cat' => 'Fiction', 'brand' => 'Parker',
                'price' => 12.99, 'buy' => 5.00, 'qty' => 80,
                'sku' => 'BK-ALCH',
                'desc' => 'A magical story about following your dreams. Over 65 million copies sold.',
                'image_query' => 'the+alchemist+book+cover',
            ],

            // ── TOYS ──
            [
                'name' => 'LEGO Star Wars Millennium Falcon',
                'cat' => 'LEGO', 'brand' => 'LEGO',
                'price' => 169.99, 'buy' => 100.00, 'qty' => 30,
                'sku' => 'LGO-SW-MF',
                'desc' => '7,541 pieces. The most detailed LEGO Falcon ever. Includes 10 minifigures.',
                'image_query' => 'lego+star+wars+millennium+falcon',
            ],
            [
                'name' => 'Hasbro Monopoly Classic',
                'cat' => 'Board Games', 'brand' => 'Hasbro',
                'price' => 19.99, 'buy' => 8.00, 'qty' => 60,
                'sku' => 'HSB-MONO',
                'desc' => 'The classic property trading game. Buy, sell, trade, and dream your way to riches.',
                'image_query' => 'monopoly+board+game+classic',
            ],

            // ── BEAUTY ──
            [
                'name' => "L'Oreal Paris Revitalift Cream",
                'cat' => 'Skincare', 'brand' => "L'Oreal",
                'price' => 29.99, 'buy' => 10.00, 'qty' => 120,
                'sku' => 'LO-REV',
                'desc' => 'Anti-aging face moisturizer with Pro-Retinol and Hyaluronic Acid.',
                'image_query' => 'loreal+face+cream+skincare',
            ],
            [
                'name' => 'Maybelline Lash Sensational',
                'cat' => 'Makeup', 'brand' => 'Maybelline',
                'price' => 11.99, 'buy' => 4.00, 'qty' => 200,
                'sku' => 'MAY-LS',
                'desc' => 'Full fan effect mascara. 10x more volume. Waterproof formula.',
                'image_query' => 'maybelline+mascara+lash',
            ],

            // ── FOOD & BEVERAGES ──
            [
                'name' => 'Starbucks Medium Roast Beans 500g',
                'cat' => 'Coffee', 'brand' => 'Starbucks',
                'price' => 18.99, 'buy' => 9.00, 'qty' => 150,
                'sku' => 'SB-MR500',
                'desc' => 'Balanced and smooth with nutty undertones. Whole bean, medium roast.',
                'image_query' => 'starbucks+coffee+beans+roasted',
            ],
            [
                'name' => 'Nestle KitKat Pack (24x)',
                'cat' => 'Snacks', 'brand' => 'Nestle',
                'price' => 24.99, 'buy' => 14.00, 'qty' => 100,
                'sku' => 'NES-KKP24',
                'desc' => 'Pack of 24 iconic KitKat bars. Crispy wafer fingers covered in milk chocolate.',
                'image_query' => 'kitkat+chocolate+bar+pack',
            ],
        ];

        $supplierIds = $suppliers->pluck('id')->toArray();
        $storagePath = public_path('uploads/products');

        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($products as $i => $p) {
            $slug = Str::slug($p['name']);
            $supplierId = $supplierIds[$i % count($supplierIds)];

            // Download image from Unsplash
            $imagePath = $this->downloadImage($p['image_query'], $storagePath, $slug, $i);

            $productData = [
                'name' => $p['name'],
                'fk_category_id' => $categories[$p['cat']] ?? null,
                'fk_brand_id' => $brands[$p['brand']] ?? null,
                'fk_supplier_id' => $supplierId,
                'fk_unit_id' => $units['Piece'],
                'sku' => $p['sku'],
                'description' => $p['desc'],
                'review_number' => rand(5, 500),
                'review_avg' => rand(35, 50) / 10,
                'status' => 'active',
            ];

            if ($imagePath) {
                $productData['image'] = $imagePath;
            }

            $product = Product::updateOrCreate(
                ['url_slug' => $slug],
                $productData
            );

            Stock::updateOrCreate(
                ['fk_product_id' => $product->id, 'batch' => 'MAIN'],
                [
                    'quantity' => $p['qty'],
                    'buy_price' => $p['buy'],
                    'sale_price' => $p['price'],
                    'status' => 'active',
                ]
            );

            if ($imagePath) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        $this->command?->info("Seeded " . count($products) . " products. Images: $successCount downloaded, $failCount failed.");

        // Reviews (only if users exist)
        $userIds = User::pluck('id')->toArray();
        if (!empty($userIds)) {
            $allProducts = Product::all();
            foreach ($allProducts as $product) {
                if ($product->reviews()->count() > 0) continue;
                $count = rand(2, 5);
                for ($r = 0; $r < $count; $r++) {
                    Review::create([
                        'fk_product_id' => $product->id,
                        'fk_user_id' => $userIds[array_rand($userIds)],
                        'rating' => rand(3, 5),
                        'review' => $this->getReviewText($product->name),
                        'status' => 'published',
                    ]);
                }
            }
        }
    }

    private function downloadImage(string $query, string $storagePath, string $slug, int $index = 0): ?string
    {
        $filename = $slug . '.jpg';
        $fullPath = $storagePath . '/' . $filename;

        // Unsplash photo IDs for each product category
        $photoMap = [
            // Phones
            'samsung+galaxy+phone' => '1610945427544-2868994ef629',
            'iphone+pro+max' => '1695048133142-1a20484d2569',
            'google+pixel+phone' => '1598327105666-3b40cc6c3691',
            // Laptops
            'macbook+pro+laptop' => '1517336714731-489689fd1ca8',
            'dell+laptop+xps' => '1593642632559-0c6d3fc62b89',
            'hp+laptop+pavilion' => '1496181133206-80ce9b88a853',
            // Audio
            'sony+headphones+wireless' => '1505740420928-5e560c06d30e',
            'jbl+bluetooth+speaker' => '1608043152269-423dbba4e7e1',
            'wireless+headphones+over+ear' => '1546435770-a3e426bf472b',
            // Accessories
            'logitech+mx+master+mouse' => '1527864550417-7fd91fc51a46',
            'samsung+oled+tv+4k' => '1593359677879-a4bb92f829d1',
            'lg+oled+television' => '1489599849927-2ee91cede3ba',
            // Wearables
            'apple+watch+series' => '1579586337278-3befd40fd17a',
            'casio+gshock+watch' => '1547996160-81dfa63595aa',
            // Shoes
            'nike+air+max+sneaker' => '1542291026-7eec264c27ff',
            'adidas+ultraboost+running' => '1600269452121-4f2416e55c28',
            'puma+rs+x+sneaker' => '1606107557195-0e29a4b5b4aa',
            // Men
            'levis+501+jeans+denim' => '1542272604-787c3835535d',
            // Women
            'h%26m+women+tshirt+cotton' => '1521572163474-6864f9cf17ab',
            // Bags
            'fossil+leather+bag+messenger' => '1553062407-98eeb64c6a62',
            // Sunglasses
            'ray+ban+aviator+sunglasses' => '1511499767153-a480b2228f3d',
            // Kitchen
            'kitchenaid+stand+mixer' => '1594631252845-20fc124a8b88',
            'philips+air+fryer' => '1626082927589-b3e5b1e5a6e5',
            // Furniture
            'ikea+kallax+shelf+storage' => '1555041469-a586c61ea9bc',
            // Fitness
            'dumbbell+set+fitness+weights' => '1534438327276-14e5300c3a48',
            'under+armour+training+shirt' => '1571019614242-c5c5dee9f50b',
            // Books
            'atomic+habits+book' => '1512820790803-83ca734da794',
            'the+alchemist+book+cover' => '1544947952-fa07a98d237f',
            // Toys
            'lego+star+wars+millennium+falcon' => '1587654780291-39c9404d7dd0',
            'monopoly+board+game+classic' => '1611371805429-8b5c1b2c34ba',
            // Beauty
            'loreal+face+cream+skincare' => '1556228578-0d85b1a4d571',
            'maybelline+mascara+lash' => '1596462502278-27bfdc403348',
            // Food
            'starbucks+coffee+beans+roasted' => '1447933601403-0c6688de566e',
            'kitkat+chocolate+bar+pack' => '1575377427642-087cf684f29d',
        ];

        // Try Unsplash with known photo ID
        if (isset($photoMap[$query])) {
            $photoId = $photoMap[$query];
            $url = "https://images.unsplash.com/photo-{$photoId}?w=600&h=600&fit=crop&auto=format&q=80";
        } else {
            // Fallback: use Unsplash search
            $url = "https://source.unsplash.com/600x600/?" . urlencode($query);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ERPSeeder/1.0)',
        ]);

        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($imageData && $httpCode === 200 && strlen($imageData) > 1000) {
            file_put_contents($fullPath, $imageData);
            return 'products/' . $filename;
        }

        // Fallback: try a different Unsplash approach with direct ID
        $fallbackIds = [
            'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=600&h=600&fit=crop',
        ];

        $fallbackUrl = $fallbackIds[$index % count($fallbackIds)] ?? $fallbackIds[0];

        $ch = curl_init($fallbackUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ERPSeeder/1.0)',
        ]);

        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($imageData && $httpCode === 200 && strlen($imageData) > 1000) {
            file_put_contents($fullPath, $imageData);
            return 'products/' . $filename;
        }

        return null;
    }

    private function getReviewText(string $product): string
    {
        $reviews = [
            "Excellent quality! Very satisfied with this purchase.",
            "Great value for money. Works exactly as described.",
            "Good product, fast delivery. Would recommend.",
            "Amazing! Exceeded my expectations.",
            "Decent product. Minor issues but overall good.",
            "Best purchase I've made this year!",
            "Love it! Perfect for everyday use.",
            "Solid build quality. Very happy with it.",
        ];
        return $reviews[array_rand($reviews)];
    }
}
