<?php


namespace Pterodactyl\Traits\Helpers;


use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait OAuth2Providers
{
    /**
     * Create settings for socialite providers
     *
     * @param $provider
     * @return array
     */
    public static function createProviderSettings($provider) {
        return [
            $provider => [
                'status' => config('oauth2.default_driver') == $provider ? true : env('OAUTH2_' . Str::upper($provider) . '_STATUS'),
                'client_id' => env('OAUTH2_' . Str::upper($provider) . '_CLIENT_ID'),
                'client_secret' => env('OAUTH2_' . Str::upper($provider) . '_CLIENT_SECRET'),
                'redirect' => env('APP_URL') . '/auth/login/oauth2/callback',
                'scopes' => env('OAUTH2_' . Str::upper($provider) . '_CLIENT_SECRET'),
                'widget_html' => env('OAUTH2_' . Str::upper($provider) . '_WIDGET_HTML'),
                'widget_css' => env('OAUTH2_' . Str::upper($provider) . '_WIDGET_CSS'),
                'listener' => env('OAUTH2_' . Str::upper($provider) . '_LISTENER'),
                'package' => env('OAUTH2_' . Str::upper($provider) . '_PACKAGE'),
            ]
        ];
    }

    /**
     * Get all providers
     * The ones in config/oauth2.php wil overwrite the generated ones
     */
    public static function getAllProviderSettings() {
        $array = config('oauth2.providers');
        foreach (preg_split('~,~', config('oauth2.all_drivers')) as $provider) {
            $array = array_merge(self::createProviderSettings($provider), $array);
        }
        return array_reverse($array);
    }

    /**
     * Get all enabled providers
     * The ones in config/oauth2.php wil overwrite the generated ones
     */
    public static function getEnabledProviderSettings() {
        $array = self::getAllProviderSettings();
        foreach ($array as $key => $value) {
            if ($value['status'] != 'true') $array = Arr::except($array, $key);
        }
        return array_reverse($array);
    }

    /**
     * Inject all enabled provider settings
     * @param $array
     * @return array
     */
    public static function injectEnabledProviders($array) {
        return array_merge($array, self::getEnabledProviderSettings());
    }

    public static function getProviderListeners() {
        $return = [];
        foreach (self::getAllProviderSettings() as $key => $value) {
            $return = array_merge($return, [$value['listener']]);
        }
        return $return;
    }

}