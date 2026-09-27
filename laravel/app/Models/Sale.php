<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'table_name', 'fk_user_id', 'invoice_id', 'type',
        'net_price', 'vat_amount', 'tax_amount', 'shipping_cost',
        'discount_amount', 'grand_total', 'paid_amount', 'buy_price',
        'sale_due', 'status', 'send_notification', 'note',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'sale_due' => 'decimal:2',
    ];

    public function details()
    {
        return $this->hasMany(SaleDetail::class, 'fk_sale_id');
    }

    public function saleItems()
    {
        return $this->details();
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'fk_reference_id')
            ->where('type', Transaction::TYPE_SALE_INCOME);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'fk_user_id');
    }
}
