<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create default role
        $adminRole = Role::firstOrCreate(
            ['name' => 'super-admin'],
            ['description' => 'Super Administrator with full access']
        );

        $managerRole = Role::firstOrCreate(
            ['name' => 'manager'],
            ['description' => 'Manager with limited access']
        );

        $cashierRole = Role::firstOrCreate(
            ['name' => 'cashier'],
            ['description' => 'Cashier with POS access only']
        );

        // Create default super admin
        $admin = Admin::firstOrCreate(
            ['email' => 'admin@erp.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'admin@erp.com',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
