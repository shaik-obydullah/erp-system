<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'fk_sale_id',
        'method',
        'amount',
        'change_amount',
        'transaction_ref',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
        ];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'fk_sale_id');
    }
}
