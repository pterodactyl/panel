<?php

namespace Pterodactyl\Providers;

use Krucas\Settings\Settings;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * An array of configuration keys to override with database values
     * if they exist.
     *
     * @var array
     */
    protected $keys = [
        'recaptcha.enabled',
        'recaptcha.secret_key',
        'recaptcha.website_key',
        'pterodactyl.guzzle.timeout',
        'pterodactyl.guzzle.connect_timeout',
        'pterodactyl.console.count',
        'pterodactyl.console.frequency',
    ];

    /**
     * Keys specific to the mail driver that are only grabbed from the database
     * when using the SMTP driver.
     *
     * @var array
     */
    protected $emailKeys = [
        'mail.host',
        'mail.port',
        'mail.from.address',
        'mail.from.name',
        'mail.encryption',
        'mail.username',
        'mail.password',
    ];

    /**
     * Keys that are encrypted and should be decrypted when set in the
     * configuration array.
     *
     * @var array
     */
    protected static $encrypted = [
        'mail.password',
    ];

    /**
     * Boot the service provider.
     *
     * @param \Illuminate\Contracts\Config\Repository    $config
     * @param \Illuminate\Contracts\Encryption\Encrypter $encrypter
     */
    public function boot(ConfigRepository $config, Encrypter $encrypter)
    {
        if ((bool) $config->get('pterodactyl.load_environment_only', false)) {
            return;
        }

        /** @var Settings $settings */
        $settings = $this->app->make('settings');

        // Only set the email driver settings from the database if we
        // are configured using SMTP as the driver.
        if ($config->get('mail.driver') === 'smtp') {
            $this->keys = array_merge($this->keys, $this->emailKeys);
        }

        foreach ($this->keys as $key) {
            $value = $settings->get('settings.' . $key, $config->get($key));
            if (in_array($key, self::$encrypted)) {
                try {
                    $value = $encrypter->decrypt($value);
                } catch (DecryptException $exception) {
                }
            }

            $config->set($key, $value);
        }
    }

    /**
     * @return array
     */
    public static function getEncryptedKeys(): array
    {
        return self::$encrypted;
    }
}
