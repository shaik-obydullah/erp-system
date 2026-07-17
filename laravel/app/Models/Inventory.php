<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'name', 'description', 'status',
        'created_by', 'updated_by', 'deleted_by',
    ];

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'fk_inventory_id');
    }
}
