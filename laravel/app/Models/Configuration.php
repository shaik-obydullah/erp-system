<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Configuration extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'setting', 'created_by', 'updated_by'];

    public $timestamps = false;

    public static function get(string $key, $default = null)
    {
        $config = static::where('name', $key)->first();
        return $config ? $config->setting : $default;
    }

    public static function set(string $key, $value)
    {
        static::updateOrCreate(['name' => $key], ['setting' => $value]);
    }

    public static function getMany(array $keys)
    {
        $results = static::whereIn('name', $keys)->pluck('setting', 'name')->toArray();
        $output = [];
        foreach ($keys as $key) {
            $output[$key] = $results[$key] ?? '';
        }
        return $output;
    }

    public static function setMany(array $data)
    {
        foreach ($data as $key => $value) {
            static::set($key, $value);
        }
    }
}
