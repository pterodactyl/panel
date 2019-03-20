<?php

namespace Pterodactyl\Traits\Helpers;

use Illuminate\Support\Str;

trait OAuth2Providers
{
    /**
     * Create settings for socialite providers.
     *
     * @param $provider
     * @return array
     */
    public function createProviderSettings($provider)
    {
        return [
            $provider => [
                'status' => config('oauth2.default_driver') == $provider ? true : config('oauth2.providers.' . $provider . '.status', env('OAUTH2_' . Str::upper($provider) . '_STATUS')),
                'client_id' => config('oauth2.providers.' . $provider . '.client_id', env('OAUTH2_' . Str::upper($provider) . '_CLIENT_ID')),
                'client_secret' => config('oauth2.providers.' . $provider . '.client_secret', env('OAUTH2_' . Str::upper($provider) . '_CLIENT_SECRET')),
                'redirect' => config('app.url') . '/oauth2/callback',
                'scopes' => config('oauth2.providers.' . $provider . '.scopes', env('OAUTH2_' . Str::upper($provider) . '_SCOPES')),
                'widget_html' => config('oauth2.providers.' . $provider . '.widget_html', env('OAUTH2_' . Str::upper($provider) . '_WIDGET_HTML')),
                'widget_css' => config('oauth2.providers.' . $provider . '.widget_css', env('OAUTH2_' . Str::upper($provider) . '_WIDGET_CSS')),
                'listener' => config('oauth2.providers.' . $provider . '.listener', env('OAUTH2_' . Str::upper($provider) . '_LISTENER')),
                'package' => config('oauth2.providers.' . $provider . '.package', env('OAUTH2_' . Str::upper($provider) . '_PACKAGE')),
            ],
        ];
    }

    /**
     * Get all providers.
     */
    public function getAllProviderSettings()
    {
        $array = config('oauth2.providers');
        foreach (preg_split('~,~', config('oauth2.all_drivers')) as $provider) {
            if (array_has($array, $provider)) {
                $array[$provider] = array_merge($array[$provider], self::createProviderSettings($provider)[$provider]);
            } else {
                $array = array_merge(self::createProviderSettings($provider), $array);
            }
        }

        return $array;
    }

    /**
     * Get all enabled providers.
     */
    public function getEnabledProviderSettings()
    {
        $array = $this->getAllProviderSettings();
        foreach ($array as $key => $value) {
            if ($value['status'] != true) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * Get array of listeners for the oauth2 providers.
     */
    public function getProviderListeners()
    {
        $return = [];
        foreach ($this->getAllProviderSettings() as $key => $value) {
            $return = array_merge($return, [$value['listener']]);
        }

        return $return;
    }
}
