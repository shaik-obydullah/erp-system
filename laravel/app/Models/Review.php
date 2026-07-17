<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'fk_product_id', 'fk_user_id', 'rating', 'review', 'status',
        'created_by', 'updated_by', 'deleted_by',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'fk_product_id');
    }
}
