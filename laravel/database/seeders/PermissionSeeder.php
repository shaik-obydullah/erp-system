<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Dashboard
            'dashboard.view' => 'Dashboard',

            // Products
            'products.view' => 'Products',
            'products.save' => 'Products',
            'products.edit' => 'Products',
            'products.delete' => 'Products',

            // Categories
            'categories.view' => 'Categories',
            'categories.save' => 'Categories',
            'categories.edit' => 'Categories',
            'categories.delete' => 'Categories',

            // Brands
            'brands.view' => 'Brands',
            'brands.save' => 'Brands',
            'brands.edit' => 'Brands',
            'brands.delete' => 'Brands',

            // Sizes
            'sizes.view' => 'Sizes',
            'sizes.save' => 'Sizes',
            'sizes.edit' => 'Sizes',
            'sizes.delete' => 'Sizes',

            // Colors
            'colors.view' => 'Colors',
            'colors.save' => 'Colors',
            'colors.edit' => 'Colors',
            'colors.delete' => 'Colors',

            // Units
            'units.view' => 'Units',
            'units.save' => 'Units',
            'units.edit' => 'Units',
            'units.delete' => 'Units',

            // Stock
            'stocks.view' => 'Stocks',
            'stocks.save' => 'Stocks',
            'stocks.edit' => 'Stocks',
            'stocks.delete' => 'Stocks',

            // Inventory
            'inventory.view' => 'Inventory',
            'inventory.save' => 'Inventory',
            'inventory.edit' => 'Inventory',
            'inventory.delete' => 'Inventory',

            // Customers
            'customers.view' => 'Customers',
            'customers.save' => 'Customers',
            'customers.edit' => 'Customers',
            'customers.delete' => 'Customers',

            // Suppliers
            'suppliers.view' => 'Suppliers',
            'suppliers.save' => 'Suppliers',
            'suppliers.edit' => 'Suppliers',
            'suppliers.delete' => 'Suppliers',

            // Sales
            'sales.view' => 'Sales',
            'sales.save' => 'Sales',
            'sales.edit' => 'Sales',
            'sales.delete' => 'Sales',

            // POS
            'pos.view' => 'POS',
            'pos.save' => 'POS',
            'pos.edit' => 'POS',
            'pos.delete' => 'POS',

            // Orders
            'orders.view' => 'Orders',
            'orders.save' => 'Orders',
            'orders.edit' => 'Orders',
            'orders.delete' => 'Orders',

            // Income
            'incomes.view' => 'Income',
            'incomes.save' => 'Income',
            'incomes.edit' => 'Income',
            'incomes.delete' => 'Income',

            // Expense
            'expenses.view' => 'Expenses',
            'expenses.save' => 'Expenses',
            'expenses.edit' => 'Expenses',
            'expenses.delete' => 'Expenses',

            // Cashbook
            'cashbook.view' => 'Cashbook',
            'cashbook.save' => 'Cashbook',
            'cashbook.edit' => 'Cashbook',
            'cashbook.delete' => 'Cashbook',

            // Transactions
            'transactions.view' => 'Transactions',
            'transactions.save' => 'Transactions',
            'transactions.edit' => 'Transactions',
            'transactions.delete' => 'Transactions',

            // Employees
            'employees.view' => 'Employees',
            'employees.save' => 'Employees',
            'employees.edit' => 'Employees',
            'employees.delete' => 'Employees',

            // Payroll
            'payrolls.view' => 'Payroll',
            'payrolls.save' => 'Payroll',
            'payrolls.edit' => 'Payroll',
            'payrolls.delete' => 'Payroll',

            // Tasks
            'tasks.view' => 'Tasks',
            'tasks.save' => 'Tasks',
            'tasks.edit' => 'Tasks',
            'tasks.delete' => 'Tasks',

            // Campaigns
            'campaigns.view' => 'Campaigns',
            'campaigns.save' => 'Campaigns',
            'campaigns.edit' => 'Campaigns',
            'campaigns.delete' => 'Campaigns',

            // Reports
            'reports.view' => 'Reports',
            'reports.save' => 'Reports',
            'reports.edit' => 'Reports',
            'reports.delete' => 'Reports',

            // Settings
            'settings.view' => 'Settings',
            'settings.save' => 'Settings',
            'settings.edit' => 'Settings',
            'settings.delete' => 'Settings',

            // Roles & Permissions
            'roles.view' => 'Roles & Permissions',
            'roles.save' => 'Roles & Permissions',
            'roles.edit' => 'Roles & Permissions',
            'roles.delete' => 'Roles & Permissions',

            // Admin Management
            'admins.view' => 'Admin Management',
            'admins.save' => 'Admin Management',
            'admins.edit' => 'Admin Management',
            'admins.delete' => 'Admin Management',

            // Notifications
            'notifications.view' => 'Notifications',
            'notifications.save' => 'Notifications',

            // Activity Log
            'activity.view' => 'Activity Log',

            // E-Commerce
            'ecommerce.view' => 'E-Commerce',
            'ecommerce.save' => 'E-Commerce',
            'ecommerce.edit' => 'E-Commerce',
            'ecommerce.delete' => 'E-Commerce',

            // Reviews
            'reviews.view' => 'Reviews',
            'reviews.save' => 'Reviews',
            'reviews.edit' => 'Reviews',
            'reviews.delete' => 'Reviews',

            // Warehouses
            'warehouses.view' => 'Warehouses',
            'warehouses.save' => 'Warehouses',
            'warehouses.edit' => 'Warehouses',
            'warehouses.delete' => 'Warehouses',

            // Shipments
            'shipments.view' => 'Shipments',
            'shipments.save' => 'Shipments',
            'shipments.edit' => 'Shipments',
            'shipments.delete' => 'Shipments',
        ];

        // Create all permissions
        $allPermissions = [];
        foreach ($permissions as $name => $group) {
            $perm = Permission::firstOrCreate(
                ['name' => $name],
                ['group' => $group]
            );
            $allPermissions[] = $perm;
        }

        // Create super-admin role with all permissions
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super-admin'],
            ['description' => 'Super Administrator with full access']
        );
        $superAdmin->permissions()->sync(Permission::pluck('id'));

        // Create admin role (all except roles.delete)
        $admin = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator with most access']
        );
        $admin->permissions()->sync(
            Permission::where('name', '!=', 'roles.delete')->pluck('id')
        );

        // Create manager role
        $manager = Role::firstOrCreate(
            ['name' => 'manager'],
            ['description' => 'Manager with operational access']
        );
        $manager->permissions()->sync(
            Permission::whereIn('name', [
                'dashboard.view',
                'products.view', 'products.save', 'products.edit',
                'categories.view', 'categories.save',
                'stocks.view', 'stocks.save', 'stocks.edit',
                'customers.view', 'customers.save', 'customers.edit',
                'sales.view', 'sales.save',
                'orders.view', 'orders.save', 'orders.edit',
                'employees.view', 'employees.save', 'employees.edit',
                'tasks.view', 'tasks.save', 'tasks.edit',
                'reports.view',
                'inventory.view', 'inventory.save', 'inventory.edit',
                'incomes.view', 'incomes.save',
                'expenses.view', 'expenses.save',
                'cashbook.view',
            ])->pluck('id')
        );

        // Create cashier role
        $cashier = Role::firstOrCreate(
            ['name' => 'cashier'],
            ['description' => 'Cashier with POS access only']
        );
        $cashier->permissions()->sync(
            Permission::whereIn('name', [
                'dashboard.view',
                'pos.view', 'pos.save',
                'products.view',
                'customers.view', 'customers.save',
                'sales.view', 'sales.save',
                'stocks.view',
            ])->pluck('id')
        );

        // Assign super-admin to default admin
        $adminUser = Admin::where('email', 'admin@erp.com')->first();
        if ($adminUser) {
            $adminUser->roles()->syncWithoutDetaching([$superAdmin->id]);
        }
    }
}
