<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public $timestamps = true;

    /** قراءة إعداد مع cache. */
    public static function get(string $key, ?string $default = null): ?string
    {
        $all = Cache::rememberForever('settings.all', fn () => static::pluck('value', 'key')->all());

        return $all[$key] ?? $default;
    }

    /** حفظ إعداد وتفريغ الـcache. */
    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
    }
}
