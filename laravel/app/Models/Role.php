<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'role_relations', 'fk_role_id', 'fk_admin_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'fk_role_id');
    }

    public function getPermissionNames(): array
    {
        return $this->permissions->pluck('name')->toArray();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }
}
