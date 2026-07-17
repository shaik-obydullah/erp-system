<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillOfMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bill_of_materials';

    public $timestamps = false;

    protected $fillable = [
        'fk_product_id', 'name', 'unit', 'quantity', 'description',
        'created_by', 'updated_by', 'deleted_by',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'fk_product_id');
    }

    public function productionPlannings()
    {
        return $this->hasMany(ProductionPlanning::class, 'fk_bill_of_material_id');
    }
}
