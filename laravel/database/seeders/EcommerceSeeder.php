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
use Illuminate\Support\Str;

class EcommerceSeeder extends Seeder
{
    public function run(): void
    {
        // Units
        $units = [];
        foreach (['Piece', 'Box', 'Kg', 'Set', 'Pair', 'Liter', 'Meter', 'Pack'] as $u) {
            $units[$u] = Unit::updateOrCreate(['name' => $u], ['status' => 'active'])->id;
        }

        // Categories (top-level)
        $catData = [
            'Electronics' => ['Phones', 'Laptops', 'Accessories', 'Audio'],
            'Fashion' => ['Men', 'Women', 'Kids', 'Shoes'],
            'Home & Living' => ['Furniture', 'Kitchen', 'Decor', 'Lighting'],
            'Sports' => ['Fitness', 'Outdoor', 'Team Sports', 'Cycling'],
            'Books' => ['Fiction', 'Non-Fiction', 'Academic', 'Comics'],
            'Beauty' => ['Skincare', 'Makeup', 'Haircare', 'Fragrances'],
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
                    ['url_slug' => Str::slug($childName)],
                    ['name' => $childName, 'fk_category_id' => $parent->id, 'status' => 'active']
                );
                $categories[$childName] = $child->id;
            }
        }

        // Brands
        $brandNames = ['Samsung', 'Apple', 'Nike', 'Adidas', 'Sony', 'LG', 'Ikea', 'Logitech', 'HP', 'Dell', 'Boat', 'JBL', 'Levis', 'H&M', 'Puma'];
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
            return;
        }

        // Products
        $products = [
            ['name' => 'Samsung Galaxy S24 Ultra', 'cat' => 'Phones', 'brand' => 'Samsung', 'price' => 1299.99, 'buy' => 950.00, 'desc' => 'Premium smartphone with S Pen, 200MP camera, and titanium frame. Features AI-powered photography and Galaxy AI suite.', 'sku' => 'SAM-S24U', 'qty' => 45],
            ['name' => 'iPhone 15 Pro Max', 'cat' => 'Phones', 'brand' => 'Apple', 'price' => 1199.99, 'buy' => 880.00, 'desc' => 'Apple flagship with A17 Pro chip, titanium design, and 5x optical zoom. USB-C and Action button.', 'sku' => 'APL-15PM', 'qty' => 38],
            ['name' => 'MacBook Pro 16" M3', 'cat' => 'Laptops', 'brand' => 'Apple', 'price' => 2499.99, 'buy' => 1850.00, 'desc' => 'Powerhouse laptop with M3 Pro chip, 18GB RAM, 512GB SSD. Liquid Retina XDR display.', 'sku' => 'APL-MBP16', 'qty' => 20],
            ['name' => 'Dell XPS 15', 'cat' => 'Laptops', 'brand' => 'Dell', 'price' => 1799.99, 'buy' => 1300.00, 'desc' => 'Stunning OLED InfinityEdge display, Intel i9, 32GB RAM, 1TB SSD. Perfect for creators.', 'sku' => 'DEL-XPS15', 'qty' => 25],
            ['name' => 'Sony WH-1000XM5', 'cat' => 'Audio', 'brand' => 'Sony', 'price' => 349.99, 'buy' => 220.00, 'desc' => 'Industry-leading noise canceling headphones. 30-hour battery, multipoint connection.', 'sku' => 'SNY-WH1000', 'qty' => 60],
            ['name' => 'JBL Charge 5', 'cat' => 'Audio', 'brand' => 'JBL', 'price' => 179.99, 'buy' => 110.00, 'desc' => 'Portable Bluetooth speaker with powerful bass, IP67 waterproof, 20-hour playtime.', 'sku' => 'JBL-CHG5', 'qty' => 80],
            ['name' => 'Nike Air Max 270', 'cat' => 'Shoes', 'brand' => 'Nike', 'price' => 159.99, 'buy' => 85.00, 'desc' => 'Iconic lifestyle sneaker with Max Air unit for all-day comfort. Breathable mesh upper.', 'sku' => 'NKE-AM270', 'qty' => 120],
            ['name' => 'Adidas Ultraboost 23', 'cat' => 'Shoes', 'brand' => 'Adidas', 'price' => 189.99, 'buy' => 100.00, 'desc' => 'Responsive running shoe with BOOST midsole and Continental rubber outsole.', 'sku' => 'ADI-UB23', 'qty' => 95],
            ['name' => 'Logitech MX Master 3S', 'cat' => 'Accessories', 'brand' => 'Logitech', 'price' => 99.99, 'buy' => 55.00, 'desc' => 'Wireless mouse with MagSpeed scroll wheel, 8K DPI sensor, USB-C charging.', 'sku' => 'LOG-MXM3S', 'qty' => 150],
            ['name' => 'Samsung 65" OLED 4K TV', 'cat' => 'Electronics', 'brand' => 'Samsung', 'price' => 1899.99, 'buy' => 1350.00, 'desc' => 'Stunning OLED display with infinite contrast, Dolby Atmos, Tizen smart TV.', 'sku' => 'SAM-OLED65', 'qty' => 15],
            ['name' => 'Levi\'s 501 Original Jeans', 'cat' => 'Men', 'brand' => 'Levis', 'price' => 89.99, 'buy' => 35.00, 'desc' => 'The original straight leg jean since 1873. Button fly, authentic fit.', 'sku' => 'LEV-501', 'qty' => 200],
            ['name' => 'H&M Oversized T-Shirt', 'cat' => 'Women', 'brand' => 'H&M', 'price' => 24.99, 'buy' => 8.00, 'desc' => 'Relaxed-fit cotton t-shirt. Soft jersey fabric, round neckline.', 'sku' => 'HM-OVT', 'qty' => 300],
            ['name' => 'Boat Rockerz 550', 'cat' => 'Audio', 'brand' => 'Boat', 'price' => 59.99, 'buy' => 25.00, 'desc' => 'Over-ear wireless headphones with 50mm drivers, 20-hour playback, padded ear cups.', 'sku' => 'BOT-R550', 'qty' => 180],
            ['name' => 'Puma RS-X Reinvention', 'cat' => 'Shoes', 'brand' => 'Puma', 'price' => 109.99, 'buy' => 55.00, 'desc' => 'Retro-inspired sneaker with bold colorway and RS foam cushioning.', 'sku' => 'PUM-RSX', 'qty' => 70],
            ['name' => 'HP Pavilion 14"', 'cat' => 'Laptops', 'brand' => 'HP', 'price' => 799.99, 'buy' => 520.00, 'desc' => 'Slim laptop with 13th gen Intel i5, 16GB RAM, 512GB SSD. FHD IPS display.', 'sku' => 'HP-PAV14', 'qty' => 35],
            ['name' => 'LG OLED C3 55"', 'cat' => 'Electronics', 'brand' => 'LG', 'price' => 1299.99, 'buy' => 900.00, 'desc' => 'OLED evo panel, α9 Gen6 AI Processor, Dolby Vision & Atmos, webOS 23.', 'sku' => 'LG-OLED55', 'qty' => 22],
        ];

        $productModels = [];
        $supplierIds = $suppliers->pluck('id')->toArray();

        foreach ($products as $i => $p) {
            $slug = Str::slug($p['name']);
            $supplierId = $supplierIds[$i % count($supplierIds)];

            $product = Product::updateOrCreate(
                ['url_slug' => $slug],
                [
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
                ]
            );
            $productModels[] = $product;

            Stock::updateOrCreate(
                ['fk_product_id' => $product->id, 'batch' => 'MAIN'],
                [
                    'quantity' => $p['qty'],
                    'buy_price' => $p['buy'],
                    'sale_price' => $p['price'],
                    'status' => 'active',
                ]
            );
        }

        // Reviews (only if users exist)
        $userIds = User::pluck('id')->toArray();
        if (!empty($userIds)) {
            foreach ($productModels as $product) {
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
