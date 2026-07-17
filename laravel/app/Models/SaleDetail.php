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
}
