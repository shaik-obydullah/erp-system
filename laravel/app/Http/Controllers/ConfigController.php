<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Currency;
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
        'company_country' => 'GB',
        'company_vat_number' => '',
        'company_website' => '',
        'currency' => 'USD',
        'currency_symbol' => '$',
        'timezone' => 'UTC',
        'date_format' => 'Y-m-d',
        'fiscal_year_start' => '01-01',
        'tax_rate' => '0',
        'vat_rate' => '20',
        'invoice_prefix' => 'INV',
        'order_prefix' => 'ORD',
        'low_stock_threshold' => '10',
        'allow_registration' => '1',
    ];

    private function currencies(): array
    {
        $currencies = config('currencies', []);

        Currency::where('is_active', true)->orderBy('code')->get()->each(function (Currency $currency) use (&$currencies) {
            $currencies[$currency->code] = [
                'name' => $currency->name,
                'symbol' => $currency->symbol,
            ];
        });

        return $currencies;
    }

    public function settings()
    {
        $settings = Configuration::getMany(array_keys($this->erpSettings));
        $isMaintenance = Configuration::get('maintenance_mode', '0') === '1';

        foreach ($this->erpSettings as $key => $default) {
            if (! array_key_exists($key, $settings) || $settings[$key] === null || $settings[$key] === '') {
                $settings[$key] = $default;
            }
        }

        $currencies = $this->currencies();

        if (! array_key_exists($settings['currency'], $currencies)) {
            $currencies[$settings['currency']] = [
                'name' => $settings['currency'],
                'symbol' => $settings['currency_symbol'],
            ];
        }

        return view('settings.index', compact('settings', 'isMaintenance', 'currencies'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:100',
            'company_email' => 'required|email|max:100',
            'company_phone' => 'nullable|string|max:20',
            'company_address' => 'nullable|string|max:255',
            'company_country' => 'nullable|string|size:2',
            'company_vat_number' => 'nullable|string|max:30',
            'company_website' => 'nullable|url|max:100',
            'currency' => 'required|string|max:10',
            'timezone' => 'required|string|max:50',
            'date_format' => 'required|string|max:20',
            'fiscal_year_start' => 'required|string|max:10',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'vat_rate' => 'required|numeric|min:0|max:100',
            'invoice_prefix' => 'nullable|string|max:10',
            'order_prefix' => 'nullable|string|max:10',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'allow_registration' => 'required|in:0,1',
        ]);

        // The configurations table does not allow a NULL setting. Empty
        // optional fields arrive as null from ConvertEmptyStringsToNull.
        $validated = array_map(
            fn ($value) => $value === null ? '' : $value,
            $validated
        );

        $currencies = $this->currencies();
        $validated['currency_symbol'] = $currencies[$validated['currency']]['symbol']
            ?? Configuration::get('currency_symbol', '$');

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
