<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Http\Requests\Admin\Settings\MailSettingsFormRequest;

class MailController extends Controller
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * @var \Krucas\Settings\Settings
     */
    private $settings;
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * MailController constructor.
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
     * Render UI for editing mail settings. This UI should only display if
     * the server is configured to send mail using SMTP.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        return view('admin.settings.mail', [
            'disabled' => $this->config->get('mail.driver') !== 'smtp',
        ]);
    }

    /**
     * Handle request to update SMTP mail settings.
     *
     * @param \Pterodactyl\Http\Requests\Admin\Settings\MailSettingsFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function update(MailSettingsFormRequest $request): RedirectResponse
    {
        if ($this->config->get('mail.driver') !== 'smtp') {
            throw new DisplayException('This feature is only available if SMTP is the selected email driver for the Panel.');
        }

        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings.' . str_replace('_', '.', $key), $value);
        }

        $this->alert->success('Email settings have been updated successfully.')->flash();

        return redirect()->route('admin.settings.mail');
    }
}
