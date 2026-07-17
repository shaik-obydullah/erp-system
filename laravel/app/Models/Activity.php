<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'fk_admin_id',
        'type',
        'name',
        'description',
        'subject_type',
        'subject_id',
        'ip_address',
        'visitor_country',
        'visitor_state',
        'visitor_city',
        'visitor_address',
        'old_data',
        'new_data',
        'updated_by',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'fk_admin_id');
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('fk_admin_id', $adminId);
    }

    public function scopeByDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('ip_address', 'like', "%{$search}%")
              ->orWhereHas('admin', function ($q2) use ($search) {
                  $q2->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
              });
        });
    }
}
