<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'stock_adjustments';

    public $timestamps = false;

    protected $fillable = [
        'fk_stock_id', 'fk_warehouse_id', 'batch', 'lot',
        'quantity', 'reason',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'fk_stock_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'fk_warehouse_id');
    }
}
