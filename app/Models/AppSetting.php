<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getMap(array $keys): array
    {
        $existing = self::query()
            ->whereIn('key', $keys)
            ->pluck('value', 'key')
            ->all();

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = array_key_exists($key, $existing) ? (string) $existing[$key] : null;
        }

        return $result;
    }

    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::query()->updateOrCreate(
                ['key' => (string) $key],
                ['value' => $value === null ? null : (string) $value]
            );
        }
    }
}
