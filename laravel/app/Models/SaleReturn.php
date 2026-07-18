<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleReturn extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'fk_sale_id', 'fk_stock_id', 'quantity', 'refund_amount',
        'reason', 'note', 'status',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'fk_sale_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'fk_stock_id');
    }
}
