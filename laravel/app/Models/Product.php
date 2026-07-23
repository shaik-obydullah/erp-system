<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'fk_brand_id', 'fk_model_id', 'fk_category_id', 'fk_subcategory_id',
        'fk_item_id', 'fk_supplier_id', 'fk_unit_id',
        'name', 'url_slug', 'image', 'sku', 'barcode',
        'size', 'color', 'specification', 'attribute',
        'review_number', 'review_avg', 'description', 'status',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->url_slug)) {
                $product->url_slug = Str::slug($product->name);
            }
        });
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'fk_brand_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'fk_category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'fk_subcategory_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'fk_supplier_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'fk_unit_id');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'fk_product_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'fk_product_id');
    }

    public function getSalePriceAttribute()
    {
        $stock = $this->stocks()->where('status', 'active')->first();
        return $stock ? $stock->sale_price : 0;
    }

    public function getBuyPriceAttribute()
    {
        $stock = $this->stocks()->where('status', 'active')->first();
        return $stock ? $stock->buy_price : 0;
    }

    public function getStockQuantityAttribute()
    {
        return $this->stocks()->where('status', 'active')->sum('quantity');
    }

    public function getParsedImagesAttribute()
    {
        if (empty($this->image)) {
            return [];
        }
        $decoded = json_decode($this->image, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        return [$this->image];
    }

    public function getFirstImageUrlAttribute()
    {
        $images = $this->parsed_images;
        if (empty($images)) {
            return null;
        }
        $first = $images[0];
        if (str_starts_with($first, 'http')) {
            return $first;
        }
        $cleanPath = preg_replace('#^products/#', '', $first);
        return asset('uploads/products/' . $cleanPath);
    }
}
