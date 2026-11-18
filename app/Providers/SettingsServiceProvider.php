<?php

namespace Pterodactyl\Providers;

use Psr\Log\LoggerInterface as Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class SettingsServiceProvider extends ServiceProvider
{
    protected array $keys = [
        'app:name',
        'app:locale',
        'app:debug',
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

    protected array $emailKeys = [
        'mail:mailers:smtp:host',
        'mail:mailers:smtp:port',
        'mail:mailers:smtp:encryption',
        'mail:mailers:smtp:username',
        'mail:mailers:smtp:password',
        'mail:from:address',
        'mail:from:name',
    ];

    protected static array $encrypted = [
        'mail:mailers:smtp:password',
    ];

    public function boot(
        ConfigRepository $config,
        Encrypter $encrypter,
        Log $log,
        SettingsRepositoryInterface $settings
    ): void {
        if ($config->get('mail.default') === 'smtp') {
            $this->keys = array_merge($this->keys, $this->emailKeys);
        }

        try {
            $values = $settings->all()->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->value];
            })->toArray();
        } catch (QueryException $exception) {
            $log->notice(
                'A query exception was encountered while trying to load settings from the database: '
                . $exception->getMessage()
            );

            return;
        }

        $proxyKey = 'settings::trustedproxy:proxies';

        if (array_key_exists($proxyKey, $values)) {
            $raw = trim((string) $values[$proxyKey]);

            $proxies = in_array($raw, ['*', '**'], true)
                ? $raw
                : array_values(array_filter(
                    array_map('trim', explode(',', $raw)),
                    static fn (string $proxy): bool => $proxy !== ''
                ));

            $config->set('trustedproxy.proxies', $proxies);
        }

        foreach ($this->keys as $key) {
            $value = array_get(
                $values,
                'settings::' . $key,
                $config->get(str_replace(':', '.', $key))
            );

            if (in_array($key, self::$encrypted, true)) {
                try {
                    $value = $encrypter->decrypt($value);
                } catch (DecryptException $exception) {
                }
            }

            if (is_string($value)) {
                switch (strtolower($value)) {
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
                        break;
                }
            }

            $config->set(str_replace(':', '.', $key), $value);
        }
    }

    public static function getEncryptedKeys(): array
    {
        return self::$encrypted;
    }
}
