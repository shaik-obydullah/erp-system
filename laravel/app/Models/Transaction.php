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

    protected $fillable = [
        'date',
        'type',
        'fk_reference_id',
        'amount',
        'paid_amount',
        'due_amount',
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
}
