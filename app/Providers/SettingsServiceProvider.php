<?php

declare(strict_types=1);

namespace Pterodactyl\Providers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface as Log;
use Pterodactyl\Models\Setting;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * An array of configuration keys to override with database values
     * if they exist.
     *
     * @var list<string>
     */
    protected array $keys = [
        'app:name',
        'app:locale',
        'recaptcha:enabled',
        'recaptcha:secret_key',
        'recaptcha:website_key',
        'pterodactyl:guzzle:timeout',
        'pterodactyl:guzzle:connect_timeout',
        'pterodactyl:console:count',
        'pterodactyl:console:frequency',
        'pterodactyl:auth:2fa_required',
        'pterodactyl:client_features:allocations:enabled',
        'pterodactyl:client_features:allocations:range_start',
        'pterodactyl:client_features:allocations:range_end',
    ];

    /**
     * Keys specific to the mail driver that are only grabbed from the database
     * when using the SMTP driver.
     *
     * @var list<string>
     */
    protected array $emailKeys = [
        'mail:mailers:smtp:host',
        'mail:mailers:smtp:port',
        'mail:mailers:smtp:encryption',
        'mail:mailers:smtp:username',
        'mail:mailers:smtp:password',
        'mail:from:address',
        'mail:from:name',
    ];

    /**
     * Keys that are encrypted and should be decrypted when set in the
     * configuration array.
     *
     * @var list<string>
     */
    protected static array $encrypted = [
        'mail:mailers:smtp:password',
    ];

    /**
     * @return list<string>
     */
    public static function getEncryptedKeys(): array
    {
        return self::$encrypted;
    }

    /**
     * Boot the service provider.
     */
    public function boot(ConfigRepository $config, Encrypter $encrypter, Log $log): void
    {
        // Only set the email driver settings from the database if we
        // are configured using SMTP as the driver.
        if ($config->get('mail.default') === 'smtp') {
            $this->keys = array_merge($this->keys, $this->emailKeys);
        }

        try {
            $values = Setting::query()->pluck('value', 'key')->all();
        } catch (QueryException $queryException) {
            $log->notice('A query exception was encountered while trying to load settings from the database: '.$queryException->getMessage());

            return;
        }

        foreach ($this->keys as $key) {
            $value = Arr::get($values, 'settings::'.$key, $config->get(str_replace(':', '.', $key)));
            if (in_array($key, self::$encrypted, true) && is_string($value)) {
                try {
                    $value = $encrypter->decrypt($value);
                } catch (DecryptException) {
                }
            }

            switch (is_string($value) ? mb_strtolower($value) : $value) {
                case 'true':
                case '(true)':
                    $value = true;
                    break;
                case 'false':
                case '(false)':
                    $value = false;
                    break;
                case 'empty':
                case '(empty)':
                    $value = '';
                    break;
                case 'null':
                case '(null)':
                    $value = null;
            }

            $config->set(str_replace(':', '.', $key), $value);
        }
    }
}
