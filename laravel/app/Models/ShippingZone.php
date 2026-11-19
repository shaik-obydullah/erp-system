<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingZone extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'countries', 'status', 'sort_order',
        'created_by', 'updated_by', 'deleted_by',
    ];

    public function methods()
    {
        return $this->hasMany(ShippingMethod::class, 'fk_shipping_zone_id');
    }

    public function activeMethods()
    {
        return $this->methods()->where('status', 'active');
    }

    public function getCountryListAttribute()
    {
        if (empty($this->countries)) {
            return [];
        }

        return json_decode($this->countries, true) ?: [];
    }

    public function matchesCountry(?string $country)
    {
        if (empty($country)) {
            return false;
        }

        $list = $this->country_list;

        if (in_array('*', $list, true)) {
            return true;
        }

        return in_array($country, $list, true);
    }
}