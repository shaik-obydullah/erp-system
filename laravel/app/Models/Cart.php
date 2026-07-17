<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cart';

    public $timestamps = false;

    protected $fillable = [
        'fk_admin_id', 'fk_user_id',
        'net_total', 'vat_total', 'tax_total', 'discount_total',
        'grand_total', 'buy_total',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'net_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function details()
    {
        return $this->hasMany(CartDetail::class, 'fk_cart_id');
    }
}
