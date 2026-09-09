<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = static::where('key', $key)->value('value');

        return $value ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * @param  array<string>  $keys
     * @return array<string, ?string>
     */
    public static function getMany(array $keys): array
    {
        $stored = static::whereIn('key', $keys)->pluck('value', 'key')->all();
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $stored[$key] ?? null;
        }

        return $result;
    }
}
