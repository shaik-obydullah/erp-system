<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Double-entry ledger
    |--------------------------------------------------------------------------
    |
    | Cashbook and transaction rows keep working exactly as before. When this
    | is enabled every financial event also posts a balanced journal entry.
    | "enforced" = false means a ledger error is logged, never blocking a sale.
    |
    */

    'enabled' => env('ACCOUNTING_GL_ENABLED', true),

    'enforced' => env('ACCOUNTING_GL_ENFORCED', false),

    /*
    |--------------------------------------------------------------------------
    | Chart of accounts
    |--------------------------------------------------------------------------
    |
    | system_key is the stable name used by code. A user may rename the
    | friendly name or code without breaking automatic posting.
    |
    */

    'chart_of_accounts' => [
        ['code' => '1000', 'system_key' => 'cash', 'name' => 'Cash', 'type' => 'asset', 'normal_balance' => 'debit', 'is_system' => true],
        ['code' => '1010', 'system_key' => 'bank', 'name' => 'Bank', 'type' => 'asset', 'normal_balance' => 'debit', 'is_system' => true],
        ['code' => '1100', 'system_key' => 'accounts_receivable', 'name' => 'Money customers owe you', 'type' => 'asset', 'normal_balance' => 'debit', 'is_system' => true],
        ['code' => '1200', 'system_key' => 'inventory', 'name' => 'Inventory', 'type' => 'asset', 'normal_balance' => 'debit', 'is_system' => true],
        ['code' => '1300', 'system_key' => 'supplier_advances', 'name' => 'Advances to suppliers', 'type' => 'asset', 'normal_balance' => 'debit', 'is_system' => true],
        ['code' => '1500', 'system_key' => 'fixed_assets', 'name' => 'Fixed assets', 'type' => 'asset', 'normal_balance' => 'debit', 'is_system' => true],

        ['code' => '2000', 'system_key' => 'accounts_payable', 'name' => 'Money you owe suppliers', 'type' => 'liability', 'normal_balance' => 'credit', 'is_system' => true],
        ['code' => '2100', 'system_key' => 'sales_tax_payable', 'name' => 'Sales tax / VAT payable', 'type' => 'liability', 'normal_balance' => 'credit', 'is_system' => true],
        ['code' => '2200', 'system_key' => 'customer_advances', 'name' => 'Customer advances', 'type' => 'liability', 'normal_balance' => 'credit', 'is_system' => true],
        ['code' => '2300', 'system_key' => 'salaries_payable', 'name' => 'Salaries payable', 'type' => 'liability', 'normal_balance' => 'credit', 'is_system' => true],

        ['code' => '3000', 'system_key' => 'owner_equity', 'name' => "Owner's equity", 'type' => 'equity', 'normal_balance' => 'credit', 'is_system' => true],
        ['code' => '3100', 'system_key' => 'retained_earnings', 'name' => 'Retained earnings', 'type' => 'equity', 'normal_balance' => 'credit', 'is_system' => true],
        ['code' => '3900', 'system_key' => 'opening_balance_equity', 'name' => 'Opening balance adjustments', 'type' => 'equity', 'normal_balance' => 'credit', 'is_system' => true],

        ['code' => '4000', 'system_key' => 'sales_revenue', 'name' => 'Sales revenue', 'type' => 'revenue', 'normal_balance' => 'credit', 'is_system' => true],
        ['code' => '4010', 'system_key' => 'sales_returns', 'name' => 'Sales returns / discounts', 'type' => 'revenue', 'normal_balance' => 'debit', 'is_system' => true],
        ['code' => '4100', 'system_key' => 'other_income', 'name' => 'Other income', 'type' => 'revenue', 'normal_balance' => 'credit', 'is_system' => true],
        ['code' => '4110', 'system_key' => 'general_income', 'name' => 'Income', 'type' => 'revenue', 'normal_balance' => 'credit', 'is_system' => true],

        ['code' => '5000', 'system_key' => 'cost_of_goods_sold', 'name' => 'Cost of goods sold', 'type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true],
        ['code' => '5100', 'system_key' => 'salaries_expense', 'name' => 'Salaries & wages', 'type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true],
        ['code' => '5200', 'system_key' => 'rent_expense', 'name' => 'Rent', 'type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true],
        ['code' => '5300', 'system_key' => 'utilities_expense', 'name' => 'Utilities', 'type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true],
        ['code' => '5400', 'system_key' => 'marketing_expense', 'name' => 'Marketing campaigns', 'type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true],
        ['code' => '5500', 'system_key' => 'inventory_adjustment', 'name' => 'Inventory adjustments / losses', 'type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true],
        ['code' => '5600', 'system_key' => 'manufacturing_expense', 'name' => 'Manufacturing & production', 'type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true],
        ['code' => '5900', 'system_key' => 'general_expense', 'name' => 'Miscellaneous expense', 'type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default account per event
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'income' => 'general_income',
        'expense' => 'general_expense',
        'sale_return' => 'sales_returns',
        'stock_in' => 'inventory',
        'stock_out' => 'inventory_adjustment',
        'salary' => 'salaries_expense',
        'campaign' => 'marketing_expense',
        'bom' => 'manufacturing_expense',
        'fixed_asset' => 'fixed_assets',
        'supplier_payment' => 'accounts_payable',
    ],
];
