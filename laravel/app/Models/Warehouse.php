<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'name', 'capacity', 'location', 'contact_number', 'email',
        'created_by', 'updated_by', 'deleted_by',
    ];

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'fk_warehouses_id');
    }
}
