<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Settings extends Model
{
    use HasFactory;

    protected $table = 'settings';

    public const CACHE_TAG = 'setting';

    public $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    public static function boot()
    {
        parent::boot();

        static::updated(function (Settings $settings) {
            Cache::forget(self::CACHE_TAG.':'.$settings->key);
        });
    }

    /**
     * @param  string  $key
     * @param $default
     * @return mixed
     */
    public static function getValueByKey(string $key, $default = null)
    {
        return Cache::rememberForever(self::CACHE_TAG.':'.$key, function () use ($default, $key) {
            $settings = self::find($key);

            return $settings ? $settings->value : $default;
        });
    }
}
