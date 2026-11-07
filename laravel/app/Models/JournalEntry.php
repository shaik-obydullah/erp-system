<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'entry_no',
        'date',
        'memo',
        'event_type',
        'source_type',
        'source_id',
        'source_key',
        'status',
        'is_auto',
        'posted_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_auto' => 'boolean',
        'posted_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function getDebitTotalAttribute(): float
    {
        return (float) $this->lines->sum('debit');
    }

    public function getCreditTotalAttribute(): float
    {
        return (float) $this->lines->sum('credit');
    }

    public function getIsBalancedAttribute(): bool
    {
        return round($this->debit_total, 2) === round($this->credit_total, 2);
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeBetween($query, $from, $to)
    {
        return $query->whereDate('date', '>=', $from)->whereDate('date', '<=', $to);
    }
}
