<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stock extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'fk_product_id', 'fk_inventory_id', 'fk_warehouses_id',
        'batch', 'lot', 'quantity', 'buy_price', 'sale_price', 'status',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'fk_product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'fk_warehouses_id');
    }
}
