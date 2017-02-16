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

namespace Pterodactyl\Services;

use Cache;
use GuzzleHttp\Client;

class VersionService
{
    protected static $versions;

    /**
     * Constructor.
     */
    public function __construct()
    {
        self::$versions = Cache::remember('versions', env('VERSION_CACHE_TIME', 60), function () {
            $client = new Client();

            try {
                $response = $client->request('GET', env('VERSION_CHECK_URL', 'https://cdn.pterodactyl.io/releases/latest.json'));

                if ($response->getStatusCode() === 200) {
                    return json_decode($response->getBody());
                } else {
                    throw new \Exception('Invalid response code.');
                }
            } catch (\Exception $ex) {
                // Failed request, just return errored version.
                return (object) [
                    'panel' => 'error',
                    'daemon' => 'error',
                    'discord' => 'https://pterodactyl.io',
                ];
            }
        });
    }

    public static function getPanel()
    {
        return self::$versions->panel;
    }

    public static function getDaemon()
    {
        return self::$versions->daemon;
    }

    public static function getDiscord()
    {
        return self::$versions->discord;
    }

    public function getCurrentPanel()
    {
        return config('app.version');
    }

    public static function isLatestPanel()
    {
        if (config('app.version') === 'canary') {
            return true;
        }

        return version_compare(config('app.version'), self::$versions->panel) >= 0;
    }

    public static function isLatestDaemon($daemon)
    {
        if ($daemon === '0.0.0-canary') {
            return true;
        }

        return version_compare($daemon, self::$versions->daemon) >= 0;
    }
}
