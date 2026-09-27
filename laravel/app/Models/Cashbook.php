<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cashbook extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cashbook';

    public $timestamps = false;

    protected $fillable = [
        'table_name',
        'fk_reference_id',
        'description',
        'in_amount',
        'out_amount',
        'amount_payable',
        'amount_receivable',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'in_amount' => 'decimal:2',
            'out_amount' => 'decimal:2',
            'amount_payable' => 'decimal:2',
            'amount_receivable' => 'decimal:2',
        ];
    }

    public function reference()
    {
        return $this->morphTo('reference', 'fk_reference_id', 'table_name');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'fk_reference_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'fk_reference_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'fk_reference_id');
    }

    public function income()
    {
        return $this->belongsTo(Income::class, 'fk_reference_id');
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class, 'fk_reference_id');
    }
}
