<?php

namespace Database\Seeders;

use App\Models\Configuration;
use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            'company_name'         => 'ERP Admin',
            'company_email'        => 'admin@example.com',
            'company_phone'        => '',
            'company_address'      => '',
            'company_website'      => '',
            'currency'             => 'USD',
            'currency_symbol'      => '$',
            'timezone'             => 'UTC',
            'date_format'          => 'Y-m-d',
            'fiscal_year_start'    => '01-01',
            'tax_rate'             => '0',
            'invoice_prefix'       => 'INV',
            'order_prefix'         => 'ORD',
            'low_stock_threshold'  => '10',
            'allow_registration'   => '1',
            'maintenance_mode'     => '0',
        ];

        foreach ($configs as $key => $value) {
            Configuration::firstOrCreate(
                ['name' => $key],
                ['setting' => $value]
            );
        }
    }
}
