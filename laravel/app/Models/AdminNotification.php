<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminNotification extends Model
{
    use SoftDeletes;

    protected $table = 'notifications';
    public $timestamps = false;

    protected $fillable = [
        'title',
        'link',
        'notification',
        'view_status',
        'fk_admin_id',
        'type',
        'module',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'fk_admin_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function markAsSeen(): void
    {
        $this->update(['view_status' => 'seen']);
    }

    public function scopeUnseen($query)
    {
        return $query->where('view_status', 'unseen');
    }

    public function scopeForAdmin($query, int $adminId)
    {
        return $query->where('fk_admin_id', $adminId)->orWhereNull('fk_admin_id');
    }
}
