<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
 
class AppSetting extends Model
{
    protected $table    = 'app_settings';
    protected $fillable = ['key', 'value', 'description'];
 
    /**
     * Lire un paramètre (avec cache 60 secondes)
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = "app_setting_{$key}";
 
        $value = Cache::remember($cacheKey, 60, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
 
        return $value;
    }
 
    /**
     * Écrire un paramètre et vider le cache
     */
   public static function set(string $key, mixed $value, ?string $description = null): void
{
    $data = ['value' => (string) $value];
    if ($description !== null) {
        $data['description'] = $description;
    }

    static::updateOrCreate(['key' => $key], $data);

    Cache::forget("app_setting_{$key}");
}
 
    /**
     * Vérifier si un paramètre booléen est activé
     */
    public static function isEnabled(string $key): bool
    {
        $value = static::get($key, '0');
        return in_array($value, ['1', 'true', 'on', 'yes'], true);
    }
}