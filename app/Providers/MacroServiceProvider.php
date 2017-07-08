<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Providers;

use File;
use Cache;
use Carbon;
use Request;
use Pterodactyl\Models\APIKey;
use Illuminate\Support\ServiceProvider;
use Pterodactyl\Services\ApiKeyService;

class MacroServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
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
                    return APIKey::where('public', $parts[0])->first();
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
