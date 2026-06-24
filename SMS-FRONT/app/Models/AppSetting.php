<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value', 'description'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever('app_setting_' . $key, function () use ($key) {
            $row = static::where('key', $key)->first();
            return $row ? $row->value : null;
        });

        return $value ?? $default;
    }

    public static function set(string $key, mixed $value, ?string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            array_filter(['value' => $value, 'description' => $description], fn($v) => $v !== null)
        );

        Cache::forget('app_setting_' . $key);
    }
}
