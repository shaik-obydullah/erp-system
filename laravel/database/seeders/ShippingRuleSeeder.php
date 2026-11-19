<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class ShippingRuleSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            [
                'name' => 'United States',
                'countries' => ['US'],
                'sort_order' => 1,
                'methods' => [
                    ['name' => 'Flat Rate', 'type' => 'flat_rate', 'cost' => 9.99, 'min_order_amount' => null, 'sort_order' => 1],
                    ['name' => 'Free Shipping', 'type' => 'free_shipping', 'cost' => 0, 'min_order_amount' => 100, 'sort_order' => 2],
                    ['name' => 'Local Pickup', 'type' => 'local_pickup', 'cost' => 0, 'min_order_amount' => null, 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Europe',
                'countries' => ['GB', 'DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'CH', 'SE', 'NO', 'DK', 'FI', 'IE', 'PT', 'PL', 'CZ', 'GR', 'HU', 'RO', 'TR', 'UA'],
                'sort_order' => 2,
                'methods' => [
                    ['name' => 'International Flat Rate', 'type' => 'flat_rate', 'cost' => 15.00, 'min_order_amount' => null, 'sort_order' => 1],
                    ['name' => 'Free Shipping', 'type' => 'free_shipping', 'cost' => 0, 'min_order_amount' => 200, 'sort_order' => 2],
                ],
            ],
            [
                'name' => 'Rest of World',
                'countries' => ['*'],
                'sort_order' => 99,
                'methods' => [
                    ['name' => 'International Flat Rate', 'type' => 'flat_rate', 'cost' => 25.00, 'min_order_amount' => null, 'sort_order' => 1],
                ],
            ],
        ];

        $created = 0;

        foreach ($zones as $zoneData) {
            $zone = ShippingZone::firstOrCreate(
                ['name' => $zoneData['name']],
                [
                    'countries' => json_encode($zoneData['countries']),
                    'status' => 'active',
                    'sort_order' => $zoneData['sort_order'],
                    'created_by' => 1,
                ]
            );

            $wasRecentlyCreated = $zone->wasRecentlyCreated;

            foreach ($zoneData['methods'] as $methodData) {
                $method = ShippingMethod::firstOrCreate(
                    [
                        'fk_shipping_zone_id' => $zone->id,
                        'name' => $methodData['name'],
                    ],
                    [
                        'type' => $methodData['type'],
                        'cost' => $methodData['cost'],
                        'min_order_amount' => $methodData['min_order_amount'],
                        'status' => 'active',
                        'sort_order' => $methodData['sort_order'],
                        'created_by' => 1,
                    ]
                );
            }

            if ($wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command?->info("Shipping zones ensured. Created: {$created}, existing zones kept intact.");
    }
}