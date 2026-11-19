<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShipmentCost extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_FREIGHT = 'freight';
    public const TYPE_DUTY = 'duty';
    public const TYPE_HANDLING = 'handling';
    public const TYPE_INSURANCE = 'insurance';
    public const TYPE_OTHER = 'other';

    public const STATUS_PAID = 'paid';
    public const STATUS_UNPAID = 'unpaid';

    public $timestamps = false;

    protected $fillable = [
        'fk_shipment_id',
        'fk_supplier_id',
        'cost_type',
        'amount',
        'cost_date',
        'payment_status',
        'paid_date',
        'invoice_number',
        'payment_method',
        'description',
        'fk_transaction_id',
        'fk_cashbook_id',
        'fk_payment_transaction_id',
        'fk_payment_cashbook_id',
        'journal_entry_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cost_date' => 'date',
        'paid_date' => 'date',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'fk_shipment_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'fk_supplier_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'fk_transaction_id');
    }

    public function cashbook()
    {
        return $this->belongsTo(Cashbook::class, 'fk_cashbook_id');
    }

    public function paymentTransaction()
    {
        return $this->belongsTo(Transaction::class, 'fk_payment_transaction_id');
    }

    public function paymentCashbook()
    {
        return $this->belongsTo(Cashbook::class, 'fk_payment_cashbook_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::STATUS_PAID;
    }

    public function typeLabel(): string
    {
        return ucfirst($this->cost_type);
    }
}
