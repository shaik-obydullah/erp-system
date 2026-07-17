<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create default super admin first (so seeder can assign role)
        Admin::firstOrCreate(
            ['email' => 'admin@erp.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'admin@erp.com',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        // Seed permissions, roles, and assign super-admin to admin
        $this->call(PermissionSeeder::class);

        // Seed configurations
        $this->call(ConfigurationSeeder::class);

        // Seed customers and suppliers
        $this->call([
            CustomerSeeder::class,
            SupplierSeeder::class,
        ]);

        // Seed ecommerce data (categories, brands, products, etc.)
        $this->call(EcommerceSeeder::class);
    }
}
