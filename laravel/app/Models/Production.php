<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Production extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'fk_bill_of_material_id', 'production_cost', 'other_cost',
        'expected_profit', 'quantity',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'production_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'expected_profit' => 'decimal:2',
    ];

    public function billOfMaterial()
    {
        return $this->belongsTo(BillOfMaterial::class, 'fk_bill_of_material_id');
    }
}
