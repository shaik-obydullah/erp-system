<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Currency extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'code', 'name', 'symbol', 'exchange_rate', 'is_base', 'is_active',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeBase(Builder $query): Builder
    {
        return $query->where('is_base', true);
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public static function convert(float $amount, string $from, string $to): float
    {
        $fromCurrency = static::findByCode($from);
        $toCurrency = static::findByCode($to);

        if (! $fromCurrency || ! $toCurrency) {
            return $amount;
        }

        $baseCurrency = static::base()->first();

        if (! $baseCurrency) {
            return $amount;
        }

        if ($from === $to) {
            return $amount;
        }

        $amountInBase = $amount / $fromCurrency->exchange_rate;

        return $amountInBase * $toCurrency->exchange_rate;
    }
}
