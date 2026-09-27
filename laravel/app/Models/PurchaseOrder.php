<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchase_orders';

    public $timestamps = false;

    protected $fillable = [
        'fk_need_id', 'fk_supplier_id', 'order_number',
        'total_amount', 'remarks', 'due_amount',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    public function need()
    {
        return $this->belongsTo(Need::class, 'fk_need_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'fk_supplier_id');
    }
}
