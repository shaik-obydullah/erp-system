<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
