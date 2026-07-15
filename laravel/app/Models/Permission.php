<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Permission extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'fk_role_id',
        'name',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'fk_role_id');
    }
}
