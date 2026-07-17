<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Need extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'description', 'date_identified',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'date_identified' => 'date',
    ];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'fk_need_id');
    }
}
