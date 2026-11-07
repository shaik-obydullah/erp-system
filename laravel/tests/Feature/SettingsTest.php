<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_can_be_updated_when_optional_fields_are_empty(): void
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'administrator']);
        $role->permissions()->attach(Permission::create(['name' => 'settings.save']));
        $admin->roles()->attach($role);

        $response = $this->actingAs($admin, 'admin')->put(route('settings.update'), [
            'company_name' => 'Test Company',
            'company_email' => 'test@example.com',
            'company_phone' => '',
            'company_address' => '',
            'company_country' => 'GB',
            'company_vat_number' => '',
            'company_website' => '',
            'currency' => 'EUR',
            'timezone' => 'Europe/London',
            'date_format' => 'Y-m-d',
            'fiscal_year_start' => '01-01',
            'tax_rate' => '0',
            'vat_rate' => '20',
            'invoice_prefix' => 'INV',
            'order_prefix' => 'ORD',
            'low_stock_threshold' => '10',
            'allow_registration' => '1',
        ]);

        $response->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('configurations', [
            'name' => 'company_phone',
            'setting' => '',
        ]);
        $this->assertDatabaseHas('configurations', [
            'name' => 'currency',
            'setting' => 'EUR',
        ]);
        $this->assertDatabaseHas('configurations', [
            'name' => 'currency_symbol',
            'setting' => '€',
        ]);
        $this->assertFalse(
            DB::table('configurations')->where('name', 'updated_by')->exists()
        );
    }
}
