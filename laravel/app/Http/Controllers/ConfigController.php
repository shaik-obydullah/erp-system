<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\View;

class ConfigController extends Controller
{
    private array $erpSettings = [
        'company_name' => 'ERP Admin',
        'company_email' => 'admin@example.com',
        'company_phone' => '',
        'company_address' => '',
        'company_website' => '',
        'currency' => 'USD',
        'currency_symbol' => '$',
        'timezone' => 'UTC',
        'date_format' => 'Y-m-d',
        'fiscal_year_start' => '01-01',
        'tax_rate' => '0',
        'invoice_prefix' => 'INV',
        'order_prefix' => 'ORD',
        'low_stock_threshold' => '10',
        'allow_registration' => '1',
    ];

    public function settings()
    {
        $settings = Configuration::getMany(array_keys($this->erpSettings));
        $isMaintenance = Configuration::get('maintenance_mode', '0') === '1';

        foreach ($this->erpSettings as $key => $default) {
            if (empty($settings[$key])) {
                $settings[$key] = $default;
            }
        }

        return view('settings.index', compact('settings', 'isMaintenance'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:100',
            'company_email' => 'required|email|max:100',
            'company_phone' => 'nullable|string|max:20',
            'company_address' => 'nullable|string|max:255',
            'company_website' => 'nullable|url|max:100',
            'currency' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:5',
            'timezone' => 'required|string|max:50',
            'date_format' => 'required|string|max:20',
            'fiscal_year_start' => 'required|string|max:10',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'invoice_prefix' => 'nullable|string|max:10',
            'order_prefix' => 'nullable|string|max:10',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'allow_registration' => 'required|in:0,1',
        ]);

        $validated['updated_by'] = auth('admin')->id();
        Configuration::setMany($validated);

        return redirect()->route('settings.index')
            ->with('success', 'ERP settings updated successfully.');
    }

    public function toggleMaintenance(Request $request)
    {
        $current = Configuration::get('maintenance_mode', '0');
        $new = $current === '1' ? '0' : '1';
        Configuration::set('maintenance_mode', $new);

        $msg = $new === '1' ? 'Maintenance mode enabled.' : 'Maintenance mode disabled. The site is now live.';
        return redirect()->route('settings.index')->with('success', $msg);
    }
}
