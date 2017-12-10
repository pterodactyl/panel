<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Http\Requests\Admin\Settings\AdvancedSettingsFormRequest;

class AdvancedController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * @var \Krucas\Settings\Settings
     */
    private $settings;

    /**
     * AdvancedController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag       $alert
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(AlertsMessageBag $alert, ConfigRepository $config)
    {
        $this->alert = $alert;
        $this->config = $config;
        $this->settings = app()->make('settings');
    }

    /**
     * Render advanced Panel settings UI.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $showRecaptchaWarning = false;
        if (
            $this->config->get('recaptcha._shipped_secret_key') === $this->config->get('recaptcha.secret_key')
            || $this->config->get('recaptcha._shipped_website_key') === $this->config->get('recaptcha.website_key')
        ) {
            $showRecaptchaWarning = true;
        }

        return view('admin.settings.advanced', [
            'showRecaptchaWarning' => $showRecaptchaWarning,
        ]);
    }

    /**
     * @param \Pterodactyl\Http\Requests\Admin\Settings\AdvancedSettingsFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(AdvancedSettingsFormRequest $request): RedirectResponse
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::' . $key, $value);
        }

        $this->kernel->call('queue:restart');
        $this->alert->success('Advanced settings have been updated successfully and the queue worker was restarted to apply these changes.')->flash();

        return redirect()->route('admin.settings.advanced');
    }
}
