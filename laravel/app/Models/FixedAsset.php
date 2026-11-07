<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAsset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fixed_assets';

    public $timestamps = false;

    protected $fillable = [
        'name', 'price', 'description', 'journal_entry_id', 'accounting_version',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'accounting_version' => 'integer',
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
