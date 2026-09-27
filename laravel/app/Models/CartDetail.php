<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cart_details';

    public $timestamps = false;

    protected $fillable = [
        'fk_cart_id', 'fk_stock_id', 'stock_name',
        'total_stock', 'qty', 'unit', 'vat', 'tax',
        'discount', 'subtotal', 'buy_price',
    ];

    protected $casts = [
        'unit' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'buy_price' => 'decimal:2',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'fk_cart_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'fk_stock_id');
    }
}
