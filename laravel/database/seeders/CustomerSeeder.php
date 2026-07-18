<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        Customer::withouttimestamps(function () {
            Customer::updateOrCreate(
                ['email' => 'john@customer.com'],
                [
                    'name' => 'John Customer',
                    'password' => Hash::make('password'),
                    'phone' => '1234567890',
                    'address' => '123 Customer St, City',
                    'status' => 'active',
                    'deleted_at' => null,
                ]
            );

            Customer::updateOrCreate(
                ['email' => 'jane@customer.com'],
                [
                    'name' => 'Jane Customer',
                    'password' => Hash::make('password'),
                    'phone' => '0987654321',
                    'address' => '456 Customer Ave, Town',
                    'status' => 'active',
                    'deleted_at' => null,
                ]
            );

            Customer::updateOrCreate(
                ['email' => 'inactive@customer.com'],
                [
                    'name' => 'Inactive Customer',
                    'password' => Hash::make('password'),
                    'status' => 'inactive',
                    'deleted_at' => null,
                ]
            );
        });
    }
}
