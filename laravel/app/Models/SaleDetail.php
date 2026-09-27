<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleDetail extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'fk_sale_id', 'fk_stock_id', 'stock_name',
        'size', 'color', 'total_stock', 'sale_stock',
        'subtotal', 'return_reason',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'fk_stock_id');
    }

    public function product()
    {
        return $this->hasOneThrough(
            Product::class,
            Stock::class,
            'id',
            'id',
            'fk_stock_id',
            'fk_product_id'
        );
    }
}
