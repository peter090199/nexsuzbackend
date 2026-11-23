<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Key/value settings stored as JSON. Never put a plain-text secret in a value: encrypt it first. */
class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = ['setting_key', 'value'];

    public static function read(string $key): ?array
    {
        $row = static::where('setting_key', $key)->first();
        $v = $row ? json_decode((string) $row->value, true) : null;

        return is_array($v) ? $v : null;
    }

    public static function write(string $key, array $value): void
    {
        static::updateOrCreate(['setting_key' => $key], ['value' => json_encode($value)]);
    }

    public static function forget(string $key): void
    {
        static::where('setting_key', $key)->delete();
    }
}
