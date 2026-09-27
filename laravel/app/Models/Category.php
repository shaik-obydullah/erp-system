<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'fk_category_id', 'serial', 'name', 'url_slug', 'status',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if (empty($category->url_slug)) {
                $category->url_slug = Str::slug($category->name);
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'fk_category_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'fk_category_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'fk_category_id');
    }
}
