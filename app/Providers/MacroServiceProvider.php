<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Providers;

use File;
use Cache;
use Carbon;
use Request;
use Pterodactyl\Models\ApiKey;
use Illuminate\Support\ServiceProvider;
use Pterodactyl\Services\ApiKeyService;

class MacroServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        File::macro('humanReadableSize', function ($path, $precision = 2) {
            $size = File::size($path);
            static $units = ['B', 'kB', 'MB', 'GB', 'TB'];

            $i = 0;
            while (($size / 1024) > 0.9) {
                $size = $size / 1024;
                $i++;
            }

            return round($size, ($i < 2) ? 0 : $precision) . ' ' . $units[$i];
        });

        Request::macro('apiKey', function () {
            if (! Request::bearerToken()) {
                return false;
            }

            $parts = explode('.', Request::bearerToken());

            if (count($parts) === 2 && strlen($parts[0]) === ApiKeyService::PUB_CRYPTO_BYTES * 2) {
                // Because the key itself isn't changing frequently, we simply cache this for
                // 15 minutes to speed up the API and keep requests flowing.
                return Cache::tags([
                    'ApiKeyMacro',
                    'ApiKeyMacro:Key:' . $parts[0],
                ])->remember('ApiKeyMacro.' . $parts[0], Carbon::now()->addMinutes(15), function () use ($parts) {
                    return ApiKey::where('public', $parts[0])->first();
                });
            }

            return false;
        });

        Request::macro('apiKeyHasPermission', function ($permission) {
            $key = Request::apiKey();
            if (! $key) {
                return false;
            }

            return Request::user()->can($permission, $key);
        });
    }
}
