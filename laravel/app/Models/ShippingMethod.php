<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'fk_shipping_zone_id', 'name', 'type', 'cost',
        'min_order_amount', 'status', 'sort_order',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
    ];

    public function zone()
    {
        return $this->belongsTo(ShippingZone::class, 'fk_shipping_zone_id');
    }

    public function isAvailable(float $subtotal)
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->type === 'free_shipping' && $this->min_order_amount !== null) {
            return $subtotal >= (float) $this->min_order_amount;
        }

        return true;
    }

    public function calculateCost(float $subtotal)
    {
        if ($this->type === 'free_shipping' || $this->type === 'local_pickup') {
            return 0.0;
        }

        return (float) $this->cost;
    }

    public function getPriceLabelAttribute(float $subtotal)
    {
        if ($this->type === 'free_shipping') {
            return 'Free';
        }

        if ($this->type === 'local_pickup') {
            return 'Free';
        }

        return number_format($this->calculateCost($subtotal), 2);
    }
}