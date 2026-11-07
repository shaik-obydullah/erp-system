<?php

namespace Tests\Unit;

use App\Models\Configuration;
use App\Services\VatCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private VatCalculator $vat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vat = app(VatCalculator::class);
    }

    public function test_it_extracts_standard_rate_vat_from_a_gross_amount(): void
    {
        $result = $this->vat->extract(120.00, VatCalculator::STANDARD);

        $this->assertSame('standard', $result['code']);
        $this->assertSame(20.0, $result['rate']);
        $this->assertSame(100.00, $result['net']);
        $this->assertSame(20.00, $result['vat']);
        $this->assertSame(120.00, $result['gross']);
    }

    public function test_it_extracts_reduced_rate_vat_from_a_gross_amount(): void
    {
        $result = $this->vat->extract(105.00, VatCalculator::REDUCED);

        $this->assertSame('reduced', $result['code']);
        $this->assertSame(5.0, $result['rate']);
        $this->assertSame(100.00, $result['net']);
        $this->assertSame(5.00, $result['vat']);
    }

    public function test_zero_exempt_and_outside_scope_charge_no_vat(): void
    {
        foreach ([VatCalculator::ZERO, VatCalculator::EXEMPT, VatCalculator::OUTSIDE_SCOPE] as $code) {
            $result = $this->vat->extract(80.00, $code);

            $this->assertSame($code, $result['code']);
            $this->assertSame(0.0, $result['rate']);
            $this->assertSame(80.00, $result['net']);
            $this->assertSame(0.00, $result['vat']);
        }
    }

    public function test_an_explicit_rate_wins_over_the_code_rate(): void
    {
        $result = $this->vat->extract(112.00, VatCalculator::STANDARD, 12.0);

        $this->assertSame(12.0, $result['rate']);
        $this->assertSame(100.00, $result['net']);
        $this->assertSame(12.00, $result['vat']);
    }

    public function test_line_rounding_is_to_two_decimal_places(): void
    {
        $result = $this->vat->line(99.99, VatCalculator::STANDARD);

        $this->assertSame(83.33, $result['net']);
        $this->assertSame(16.66, $result['vat']);
        $this->assertSame(99.99, $result['gross']);
    }

    public function test_discount_is_allocated_proportionally_before_vat_is_recalculated(): void
    {
        $lines = [
            $this->vat->line(60.00, VatCalculator::STANDARD),
            $this->vat->line(40.00, VatCalculator::ZERO),
        ];

        $discounted = $this->vat->applyDiscount($lines, 10.00);
        $summary = $this->vat->summary($discounted);

        $this->assertSame(54.00, $discounted[0]['gross']);
        $this->assertSame(45.00, $discounted[0]['net']);
        $this->assertSame(9.00, $discounted[0]['vat']);
        $this->assertSame(36.00, $discounted[1]['gross']);
        $this->assertSame(36.00, $discounted[1]['net']);
        $this->assertSame(0.00, $discounted[1]['vat']);

        $this->assertSame(90.00, $summary['gross']);
        $this->assertSame(81.00, $summary['net']);
        $this->assertSame(9.00, $summary['vat']);
    }

    public function test_summary_groups_totals_by_vat_rate(): void
    {
        $lines = [
            $this->vat->line(120.00, VatCalculator::STANDARD),
            $this->vat->line(105.00, VatCalculator::REDUCED),
            $this->vat->line(50.00, VatCalculator::ZERO),
        ];

        $summary = $this->vat->summary($lines);

        $this->assertSame(275.00, $summary['gross']);
        $this->assertSame(250.00, $summary['net']);
        $this->assertSame(25.00, $summary['vat']);
        $this->assertCount(3, $summary['breakdown']);

        $byCode = collect($summary['breakdown'])->keyBy('code');

        $this->assertSame(20.00, $byCode['standard']['vat']);
        $this->assertSame(5.00, $byCode['reduced']['vat']);
        $this->assertSame(0.00, $byCode['zero']['vat']);
    }

    public function test_default_rate_falls_back_to_uk_standard_rate(): void
    {
        $this->assertSame(20.0, $this->vat->defaultRate());
        $this->assertSame('standard', $this->vat->defaultCode());
    }

    public function test_default_rate_uses_the_configured_setting(): void
    {
        Configuration::set('vat_rate', '5');

        $this->assertSame(5.0, $this->vat->defaultRate());
        $this->assertSame('reduced', $this->vat->defaultCode());
    }
}
