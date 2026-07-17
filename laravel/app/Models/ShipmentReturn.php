<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShipmentReturn extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'shipment_returns';

    public $timestamps = false;

    protected $fillable = [
        'fk_po_id', 'invoice_amount', 'return_reason',
        'status', 'remark',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'invoice_amount' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'fk_po_id');
    }
}
