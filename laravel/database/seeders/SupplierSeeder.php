<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::updateOrCreate(
            ['email' => 'acme@supplier.com'],
            [
                'name' => 'Acme Supplies',
                'password' => Hash::make('password'),
                'mobile' => '5551234567',
                'address' => '100 Supply Rd, Industrial Zone',
                'balance' => 5000.00,
                'status' => 'active',
            ]
        );

        Supplier::updateOrCreate(
            ['email' => 'global@supplier.com'],
            [
                'name' => 'Global Materials',
                'password' => Hash::make('password'),
                'mobile' => '5559876543',
                'address' => '200 Material Blvd, Commerce City',
                'balance' => 12500.00,
                'status' => 'active',
            ]
        );

        Supplier::updateOrCreate(
            ['email' => 'inactive@supplier.com'],
            [
                'name' => 'Inactive Supplier',
                'password' => Hash::make('password'),
                'status' => 'inactive',
            ]
        );
    }
}
