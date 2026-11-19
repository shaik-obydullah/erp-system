<?php

namespace App\Services;

use App\Models\Configuration;
use App\Models\Stock;

/**
 * Central UK VAT calculator.
 *
 * All entered prices are VAT-inclusive (gross). VAT is extracted from the
 * gross amount using:
 *
 *     net = gross / (1 + rate)
 *     VAT = gross - net
 *
 * Calculations are performed per line and rounded to two decimal places, then
 * document totals are built by summing the rounded line values. This keeps
 * every document self-consistent and avoids invoice-level rounding drift.
 */
class VatCalculator
{
    public const STANDARD = 'standard';

    public const REDUCED = 'reduced';

    public const ZERO = 'zero';

    public const EXEMPT = 'exempt';

    public const OUTSIDE_SCOPE = 'outside_scope';

    /** UK VAT rates. A rate is a percentage, so 20 means 20%. */
    public const RATES = [
        self::STANDARD => 20.0,
        self::REDUCED => 5.0,
        self::ZERO => 0.0,
        self::EXEMPT => 0.0,
        self::OUTSIDE_SCOPE => 0.0,
    ];

    public const LABELS = [
        self::STANDARD => 'Standard rate (20%)',
        self::REDUCED => 'Reduced rate (5%)',
        self::ZERO => 'Zero-rated (0%)',
        self::EXEMPT => 'Exempt',
        self::OUTSIDE_SCOPE => 'Outside scope',
    ];

    /**
     * @return array<string, float>
     */
    public function rates(): array
    {
        return self::RATES;
    }

    /**
     * @return array<string, string>
     */
    public function labels(): array
    {
        return self::LABELS;
    }

    public function label(?string $code): string
    {
        return self::LABELS[$code] ?? 'Standard rate (20%)';
    }

    /**
     * Settings default VAT rate. Existing installations that have never
     * configured VAT fall back to the UK standard rate of 20%.
     */
    public function defaultRate(): float
    {
        $configured = Configuration::get('vat_rate');

        if ($configured === null || $configured === '') {
            return 20.0;
        }

        return round(max(0, (float) $configured), 2);
    }

    public function defaultCode(): string
    {
        return $this->codeForRate($this->defaultRate());
    }

    public function codeForRate(float $rate): string
    {
        if ($rate <= 0.0) {
            return self::ZERO;
        }

        if (abs($rate - 20.0) < 0.005) {
            return self::STANDARD;
        }

        if (abs($rate - 5.0) < 0.005) {
            return self::REDUCED;
        }

        return $rate > 5.0 ? self::STANDARD : self::REDUCED;
    }

    public function rateForCode(?string $code, ?float $fallback = null): float
    {
        if ($code !== null && array_key_exists($code, self::RATES)) {
            return (float) self::RATES[$code];
        }

        return $fallback ?? $this->defaultRate();
    }

    /**
     * Resolve a VAT code and rate. An explicit rate wins; otherwise the code's
     * rate is used; otherwise the caller/settings fallback applies.
     *
     * @return array{code: string, rate: float}
     */
    public function resolve(?string $code = null, ?float $rate = null, ?float $fallback = null): array
    {
        $resolvedRate = $rate !== null
            ? round(max(0, (float) $rate), 2)
            : $this->rateForCode($code, $fallback ?? $this->defaultRate());

        $resolvedCode = ($code !== null && array_key_exists($code, self::RATES))
            ? $code
            : $this->codeForRate($resolvedRate);

        return ['code' => $resolvedCode, 'rate' => $resolvedRate];
    }

    /**
     * VAT treatment for a stock line. Stock carries its own treatment because a
     * product can be bought and sold in several batches at different rates.
     * Stock without an explicit treatment uses the configured default.
     *
     * @return array{code: string, rate: float}
     */
    public function forStock(?Stock $stock, ?float $fallback = null): array
    {
        if (! $stock) {
            return $this->resolve(null, null, $fallback);
        }

        return $this->resolve(
            $stock->vat_code,
            $stock->vat_rate !== null ? (float) $stock->vat_rate : null,
            $fallback
        );
    }

    /**
     * Extract net and VAT from a VAT-inclusive gross amount.
     *
     * @return array{code: string, rate: float, net: float, vat: float, gross: float}
     */
    public function extract(float $gross, ?string $code = null, ?float $rate = null, ?float $fallback = null): array
    {
        $treatment = $this->resolve($code, $rate, $fallback);
        $gross = round(max(0, $gross), 2);

        if ($treatment['rate'] <= 0.0 || $gross <= 0.0) {
            return [
                'code' => $treatment['code'],
                'rate' => $treatment['rate'],
                'net' => $gross,
                'vat' => 0.0,
                'gross' => $gross,
            ];
        }

        $net = round($gross / (1 + ($treatment['rate'] / 100)), 2);

        return [
            'code' => $treatment['code'],
            'rate' => $treatment['rate'],
            'net' => $net,
            'vat' => round($gross - $net, 2),
            'gross' => $gross,
        ];
    }

    /**
     * Build a calculated line from a gross line amount.
     *
     * @return array{code: string, rate: float, net: float, vat: float, gross: float}
     */
    public function line(float $gross, ?string $code = null, ?float $rate = null, ?float $fallback = null): array
    {
        return $this->extract($gross, $code, $rate, $fallback);
    }

    /**
     * Apply a document-level (gross) discount proportionally across lines and
     * recalculate each line's net and VAT.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    public function applyDiscount(array $lines, float $discount): array
    {
        $lines = array_values($lines);
        $discount = round(max(0, $discount), 2);

        if ($discount <= 0 || empty($lines)) {
            return $lines;
        }

        $totalGross = round(array_sum(array_map(
            fn (array $line) => round((float) ($line['gross'] ?? 0), 2),
            $lines
        )), 2);

        if ($totalGross <= 0) {
            return $lines;
        }

        $discount = min($discount, $totalGross);
        $remaining = $discount;
        $last = count($lines) - 1;

        foreach ($lines as $index => $line) {
            $gross = round((float) ($line['gross'] ?? 0), 2);

            $lineDiscount = $index === $last
                ? $remaining
                : round($discount * ($gross / $totalGross), 2);

            $lineDiscount = round(max(0, min($lineDiscount, $remaining, $gross)), 2);
            $remaining = round($remaining - $lineDiscount, 2);

            $discountedGross = round($gross - $lineDiscount, 2);

            // Preserve an explicit line rate (e.g. a custom stock rate) instead
            // of falling back to the code's default rate after the discount.
            $vatCode = $line['vat_code'] ?? $line['code'] ?? null;
            $vatRate = $line['vat_rate'] ?? $line['rate'] ?? null;

            $calculated = $this->extract(
                $discountedGross,
                $vatCode,
                $vatRate !== null ? (float) $vatRate : null
            );

            $lines[$index] = array_merge($line, $calculated, [
                'discount' => $lineDiscount,
                'gross' => $discountedGross,
            ]);
        }

        return $lines;
    }

    /**
     * Total a set of calculated lines and produce a VAT-rate breakdown.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return array{net: float, vat: float, gross: float, breakdown: array<int, array<string, mixed>>}
     */
    public function summary(array $lines): array
    {
        $net = 0.0;
        $vat = 0.0;
        $gross = 0.0;
        $breakdown = [];

        foreach ($lines as $line) {
            $lineNet = round((float) ($line['net'] ?? 0), 2);
            $lineVat = round((float) ($line['vat'] ?? 0), 2);
            $lineGross = round((float) ($line['gross'] ?? 0), 2);

            $net = round($net + $lineNet, 2);
            $vat = round($vat + $lineVat, 2);
            $gross = round($gross + $lineGross, 2);

            $code = $line['code'] ?? $line['vat_code'] ?? $this->defaultCode();
            $rate = round((float) ($line['rate'] ?? $line['vat_rate'] ?? $this->rateForCode($code)), 2);
            $key = $code.'|'.$rate;

            if (! isset($breakdown[$key])) {
                $breakdown[$key] = [
                    'code' => $code,
                    'label' => $this->label($code),
                    'rate' => $rate,
                    'net' => 0.0,
                    'vat' => 0.0,
                    'gross' => 0.0,
                ];
            }

            $breakdown[$key]['net'] = round($breakdown[$key]['net'] + $lineNet, 2);
            $breakdown[$key]['vat'] = round($breakdown[$key]['vat'] + $lineVat, 2);
            $breakdown[$key]['gross'] = round($breakdown[$key]['gross'] + $lineGross, 2);
        }

        return [
            'net' => $net,
            'vat' => $vat,
            'gross' => $gross,
            'breakdown' => array_values($breakdown),
        ];
    }
}
