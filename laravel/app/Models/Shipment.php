<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'fk_po_id', 'fk_warehouse_id', 'tracking_number',
        'received_date', 'status', 'remark',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'received_date' => 'date',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'fk_po_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'fk_warehouse_id');
    }
}
