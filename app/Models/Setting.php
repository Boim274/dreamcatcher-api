<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function getGroup(string $group)
    {
        return static::where('group', $group)->pluck('value', 'key')->toArray();
    }

    public static function setValues(array $data, string $group = 'general')
    {
        foreach ($data as $key => $value) {
            static::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );
        }
    }
}
