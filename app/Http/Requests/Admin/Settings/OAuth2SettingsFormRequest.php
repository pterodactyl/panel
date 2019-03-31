<?php

namespace Pterodactyl\Http\Requests\Admin\Settings;

use Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class OAuth2SettingsFormRequest extends AdminFormRequest
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * OAuth2SettingsFormRequest constructor.
     *
     * @param ConfigRepository $config
     */
    public function __construct(ConfigRepository $config)
    {
        parent::__construct();
        $this->config = $config;
    }

    /**
     * Return all of the rules to apply to this request's data.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'oauth2:enabled' => 'required|in:true,false',
            'oauth2:required' => 'required|integer|in:0,1,2',
            'oauth2:providers:new' => 'sometimes|nullable|string|regex:[a-zA-Z0-9\-_\,]+',
            'oauth2:providers:deleted' => 'sometimes|nullable|string|regex:[a-zA-Z0-9\-_\,]+',
            // If in the list of all drivers + new drivers - delete drivers
            'oauth2:default_driver' => ['required', 'string', Rule::in(Arr::except(preg_split('~,~', $this->config->get('oauth2.all_drivers')), preg_split('~,~', Request::input('oauth2:provider:new'))), preg_split('~,~', Request::input('oauth2:provider:deleted')))],
        ];

        // Each provider settings
        $all_drivers = preg_split('~,~', $this->config->get('oauth2.all_drivers'));

        foreach (preg_split('~,~', Request::input('oauth2:providers:deleted')) as $provider) {
            if (($key = array_search($provider, $all_drivers)) !== false) {
                unset($all_drivers[$key]);
            }
        }

        foreach (array_filter($all_drivers) as $provider) {
            $array = [
                'oauth2:providers:' . $provider . ':status' => 'sometimes|nullable|string|in:true,false',
                'oauth2:providers:' . $provider . ':listener' => 'sometimes|nullable|string',
                'oauth2:providers:' . $provider . ':client_id' => Request::input('oauth2:providers:' . $provider . ':status') == 'true' ? 'required|string' : 'sometimes|nullable|string',
                'oauth2:providers:' . $provider . ':client_secret' => 'sometimes|nullable|string',
                'oauth2:providers:' . $provider . ':scopes' => 'sometimes|nullable|string',
                'oauth2:providers:' . $provider . ':widget_html' => 'sometimes|nullable|string',
                'oauth2:providers:' . $provider . ':widget_css' => 'sometimes|nullable|string',
            ];
            $rules = array_merge($rules, $array);
        }

        $all_drivers = preg_split('~,~', Request::input('oauth2:providers:new'));

        foreach (preg_split('~,~', Request::input('oauth2:providers:deleted')) as $provider) {
            if (($key = array_search($provider, $all_drivers)) !== false) {
                unset($all_drivers[$key]);
            }
        }
        foreach (array_filter($all_drivers) as $provider) {
            $array = [
                'oauth2:providers:' . $provider . ':status' => 'required|in:true,false',
                'oauth2:providers:' . $provider . ':name' => ['string', Rule::notIn(preg_split('~,~', $this->config->get('oauth2.all_drivers')))],
                'oauth2:providers:' . $provider . ':package' => 'sometimes|string',
                'oauth2:providers:' . $provider . ':listener' => 'sometimes|string',
                'oauth2:providers:' . $provider . ':client_id' => 'required|string',
                'oauth2:providers:' . $provider . ':client_secret' => 'required|string',
                'oauth2:providers:' . $provider . ':scopes' => 'sometimes|string',
                'oauth2:providers:' . $provider . ':widget_html' => 'required|string',
                'oauth2:providers:' . $provider . ':widget_css' => 'sometimes|string',
            ];
            $rules = array_merge($rules, $array);
        }

        return $rules;
    }

    /**
     * @return array
     */
    public function attributes()
    {
        $rules = [
            'oauth2:enabled' => 'OAuth2 Status',
            'oauth2:required' => 'Require OAuth2 Authentication',
            'oauth2:default_driver' => 'OAuth2 Default Provider',
            'oauth2:provider:new' => 'New OAuth2 Providers',
            'oauth2:provider:deleted' => 'Deleted OAuth2 Providers',
        ];

        // Each provider settings
        $all_drivers = preg_split('~,~', $this->config->get('oauth2.all_drivers'));

        foreach (preg_split('~,~', Request::input('oauth2:providers:deleted')) as $provider) {
            if (($key = array_search($provider, $all_drivers)) !== false) {
                unset($all_drivers[$key]);
            }
        }

        foreach (array_filter($all_drivers) as $provider) {
            $array = [
                'oauth2:providers:' . $provider . ':status' => Str::ucfirst($provider) . ' Provider Status',
                'oauth2:providers:' . $provider . ':listener' => Str::ucfirst($provider) . ' Provider Listener',
                'oauth2:providers:' . $provider . ':client_id' => Str::ucfirst($provider) . ' OAuth2 Client ID',
                'oauth2:providers:' . $provider . ':client_secret' => Str::ucfirst($provider) . ' OAuth2 Client Secret',
                'oauth2:providers:' . $provider . ':scopes' => Str::ucfirst($provider) . ' OAuth2 Scopes',
                'oauth2:providers:' . $provider . ':widget_html' => Str::ucfirst($provider) . ' Widget html',
                'oauth2:providers:' . $provider . ':widget_css' => Str::ucfirst($provider) . ' Widget css',
            ];
            $rules = array_merge($rules, $array);
        }

        $all_drivers = preg_split('~,~', Request::input('oauth2:providers:new'));

        foreach (preg_split('~,~', Request::input('oauth2:providers:deleted')) as $provider) {
            if (($key = array_search($provider, $all_drivers)) !== false) {
                unset($all_drivers[$key]);
            }
        }
        foreach (array_filter($all_drivers) as $provider) {
            $array = [
                'oauth2:providers:' . $provider . ':status' =>  Str::ucfirst($provider) . ' Provider Status',
                'oauth2:providers:' . $provider . ':name' =>  Str::ucfirst($provider) . ' Provider Name',
                'oauth2:providers:' . $provider . ':package' =>  Str::ucfirst($provider) . 'Provider Package',
                'oauth2:providers:' . $provider . ':listener' =>  Str::ucfirst($provider) . ' Provider Listener',
                'oauth2:providers:' . $provider . ':client_id' =>  Str::ucfirst($provider) . ' OAuth2 Client ID',
                'oauth2:providers:' . $provider . ':client_secret' =>  Str::ucfirst($provider) . ' OAuth2 Client Secret',
                'oauth2:providers:' . $provider . ':scopes' =>  Str::ucfirst($provider) . ' OAuth2 Scopes',
                'oauth2:providers:' . $provider . ':widget_html' =>  Str::ucfirst($provider) . ' Widget html',
                'oauth2:providers:' . $provider . ':widget_css' =>  Str::ucfirst($provider) . ' Widget css',
            ];
            $rules = array_merge($rules, $array);
        }

        return $rules;
    }
}
