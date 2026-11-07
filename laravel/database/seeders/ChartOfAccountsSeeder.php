<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('accounting.chart_of_accounts', []) as $account) {
            Account::updateOrCreate(
                ['code' => $account['code']],
                [
                    'system_key' => $account['system_key'],
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'normal_balance' => $account['normal_balance'],
                    'is_system' => $account['is_system'] ?? true,
                    'is_active' => true,
                ]
            );
        }
    }
}
