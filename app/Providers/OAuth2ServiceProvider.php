<?php

namespace Pterodactyl\Providers;

use Psr\Log\LoggerInterface as Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\ServiceProvider;
use Pterodactyl\Traits\Helpers\OAuth2Providers;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class OAuth2ServiceProvider extends ServiceProvider
{
    use OAuth2Providers;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $log;

    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var array original values from services.php
     */
    protected $services;

    /**
     * Boot the service provider.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     * @param \Psr\Log\LoggerInterface $log
     * @param \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface $settings
     */
    public function boot(ConfigRepository $config, Log $log, SettingsRepositoryInterface $settings)
    {
        $this->config = $config;
        $this->log = $log;
        $this->settings = $settings;

        $this->services = $config->get('services');

        $this->app->singleton('oauth2ServiceProvider', function () {
            return $this;
        });

        $this->updateConfig();
    }

    public function updateConfig()
    {
        $providers = [];

        foreach (preg_split('~,~', $this->config->get('oauth2.all_drivers')) as $provider) {
            $array = [
                'oauth2:providers:' . $provider . ':status',
                'oauth2:providers:' . $provider . ':package',
                'oauth2:providers:' . $provider . ':listener',
                'oauth2:providers:' . $provider . ':client_id',
                'oauth2:providers:' . $provider . ':client_secret',
                'oauth2:providers:' . $provider . ':scopes',
                'oauth2:providers:' . $provider . ':widget_html',
                'oauth2:providers:' . $provider . ':widget_css',
            ];
            $providers = array_merge($providers, $array);
        }

        try {
            $values = $this->settings->all()->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->value];
            })->toArray();
        } catch (QueryException $exception) {
            $this->log->notice('A query exception was encountered while trying to load settings from the database: ' . $exception->getMessage());

            return;
        }

        foreach ($providers as $key) {
            $value = array_get($values, 'settings::' . $key, $this->config->get(str_replace(':', '.', $key)));

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
            }

            $this->config->set(str_replace(':', '.', $key), $value);
        }

        $this->config->set('services', array_merge($this->services, $this->getEnabledProviderSettings()));
    }
}
