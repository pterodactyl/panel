<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Console\Kernel;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Traits\Helpers\OAuth2Providers;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Settings\OAuth2SettingsFormRequest;

class OAuth2Controller extends Controller
{
    use OAuth2Providers;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * @var \Illuminate\Contracts\Console\Kernel
     */
    private $kernel;

    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    private $settings;

    /**
     * OAuth2Controller constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag $alert
     * @param \Illuminate\Contracts\Config\Repository $config
     * @param \Illuminate\Contracts\Console\Kernel $kernel
     * @param \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface $settings
     */
    public function __construct(
        AlertsMessageBag $alert,
        ConfigRepository $config,
        Kernel $kernel,
        SettingsRepositoryInterface $settings
    ) {
        $this->alert = $alert;
        $this->config = $config;
        $this->kernel = $kernel;
        $this->settings = $settings;
    }

    /**
     * Render OAuth2 settings UI.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $providers = $this->getAllProviderSettings();

        return view('admin.settings.oauth2', compact('providers'));
    }

    /**
     * @param \Pterodactyl\Http\Requests\Admin\Settings\OAuth2SettingsFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(OAuth2SettingsFormRequest $request): RedirectResponse
    {
        $array = $request->normalize();

        $all_drivers = array_merge(preg_split('~,~', $this->config->get('oauth2.all_drivers')), preg_split('~,~', $array['oauth2:providers:new']));

        foreach (preg_split('~,~', $array['oauth2:providers:deleted']) as $provider) {
            if (($key = array_search($provider, $all_drivers)) !== false) {
                unset($all_drivers[$key]);
            }
        }

        $all_drivers = array_filter($all_drivers);

        $this->settings->set('settings::oauth2:all_drivers', implode(',', $all_drivers));

        foreach (preg_split('~,~', $array['oauth2:providers:deleted']) as $provider) {
            $this->settings->forget('settings::oauth2:providers:' . $provider . ':status');
            $this->settings->forget('settings::oauth2:providers:' . $provider . ':name');
            $this->settings->forget('settings::oauth2:providers:' . $provider . ':package');
            $this->settings->forget('settings::oauth2:providers:' . $provider . ':listener');
            $this->settings->forget('settings::oauth2:providers:' . $provider . ':client_id');
            $this->settings->forget('settings::oauth2:providers:' . $provider . ':client_secret');
            $this->settings->forget('settings::oauth2:providers:' . $provider . ':scopes');
            $this->settings->forget('settings::oauth2:providers:' . $provider . ':widget_html');
            $this->settings->forget('settings::oauth2:providers:' . $provider . ':widget_css');
        }

        $array = Arr::except($array, ['oauth2:providers:new', 'oauth2:providers:deleted']);
        foreach ($array as $key => $value) {
            // Unescape
            if (Str::endsWith($key, ':widget_html') || Str::endsWith($key, ':widget_css')) {
                $value = html_entity_decode(preg_replace('/%u([0-9a-f]{3,4})/i', '&#x\\1;', urldecode($value)), null, 'UTF-8');
            }
            // Replace escaped slash
            if (Str::endsWith($key, ':listener')) {
                $value = preg_replace('~//~', '/', preg_replace('~\\\\\\\\~', '\\\\', $value));
            }
            $this->settings->set('settings::' . $key, $value);
        }

        // Enable default driver
        $this->settings->set('settings::oauth2:providers:' . $array['oauth2:default_driver'] . ':status', 'true');

        app('oauth2ServiceProvider')->updateConfig();

        if (array_key_exists('oauth2:providers:new', $array)) {
            $this->kernel->call('p:oauth2:packages');
        }
        $this->kernel->call('queue:restart');
        $this->alert->success(__('admin/settings.oauth2.success_response'))->flash();

        return redirect()->route('admin.settings.oauth2');
    }
}
