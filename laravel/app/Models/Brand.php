<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'fk_brand_id', 'serial', 'name', 'url_slug', 'status',
        'created_by', 'updated_by', 'deleted_by',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'fk_brand_id');
    }
}
