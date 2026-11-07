<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'transactions';

    public $timestamps = false;

    const TYPE_USER_FUND = 'userFund';
    const TYPE_SUPPLIER_DEPOSIT = 'supplierDeposit';
    const TYPE_SUPPLIER_PAYMENT = 'supplierPayment';
    const TYPE_BOM_EXPENSE = 'bomExpense';
    const TYPE_SALARY_PAYMENT = 'salaryPayment';
    const TYPE_CAMPAIGN_EXPENSE = 'campaignExpense';
    const TYPE_STOCK_IN = 'stockIn';
    const TYPE_STOCK_OUT = 'stockOut';
    const TYPE_SALE_INCOME = 'saleIncome';
    const TYPE_SALE_DUE = 'saleDue';
    const TYPE_FIXED_ASSET = 'fixedAsset';
    const TYPE_INCOME = 'income';
    const TYPE_EXPENSE = 'expense';
    const TYPE_MISC_INCOME = 'miscIncome';
    const TYPE_MISC_EXPENSE = 'miscExpense';
    const TYPE_SHIPMENT_COST = 'shipmentCost';
    const TYPE_STOCK_ADJUSTMENT_LOSS = 'stockAdjustmentLoss';
    const TYPE_STOCK_ADJUSTMENT_GAIN = 'stockAdjustmentGain';

    protected $fillable = [
        'date',
        'type',
        'fk_reference_id',
        'amount',
        'paid_amount',
        'due_amount',
        'journal_entry_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_amount' => 'decimal:2',
        ];
    }

    public function cashbookEntries()
    {
        return $this->hasMany(Cashbook::class, 'fk_reference_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_USER_FUND => 'Customer Fund',
            self::TYPE_SUPPLIER_DEPOSIT => 'Supplier Advance',
            self::TYPE_SUPPLIER_PAYMENT => 'Supplier Payment',
            self::TYPE_BOM_EXPENSE => 'Production Cost',
            self::TYPE_SALARY_PAYMENT => 'Salary Payment',
            self::TYPE_CAMPAIGN_EXPENSE => 'Campaign Expense',
            self::TYPE_STOCK_IN => 'Stock In',
            self::TYPE_STOCK_OUT => 'Stock Out',
            self::TYPE_SALE_INCOME => 'Sale Income',
            self::TYPE_SALE_DUE => 'Sale Due',
            self::TYPE_FIXED_ASSET => 'Fixed Asset',
            self::TYPE_INCOME => 'Income',
            self::TYPE_EXPENSE => 'Expense',
            self::TYPE_MISC_INCOME => 'Misc Income',
            self::TYPE_MISC_EXPENSE => 'Misc Expense',
            self::TYPE_SHIPMENT_COST => 'Shipment Cost',
            self::TYPE_STOCK_ADJUSTMENT_LOSS => 'Stock Adjustment Loss',
            self::TYPE_STOCK_ADJUSTMENT_GAIN => 'Stock Adjustment Return',
            default => ucfirst((string) $this->type),
        };
    }
}
