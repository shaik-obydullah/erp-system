<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'name', 'attribute', 'media', 'content',
        'type', 'slug', 'status', 'sort_order',
        'created_by', 'updated_by', 'deleted_by',
    ];

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }
}
