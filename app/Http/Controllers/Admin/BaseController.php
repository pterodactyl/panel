<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Admin;

use Krucas\Settings\Settings;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\BaseFormRequest;
use Pterodactyl\Services\Helpers\SoftwareVersionService;

class BaseController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Krucas\Settings\Settings
     */
    protected $settings;

    /**
     * @var \Pterodactyl\Services\Helpers\SoftwareVersionService
     */
    protected $version;

    /**
     * BaseController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                    $alert
     * @param \Krucas\Settings\Settings                            $settings
     * @param \Pterodactyl\Services\Helpers\SoftwareVersionService $version
     */
    public function __construct(
        AlertsMessageBag $alert,
        Settings $settings,
        SoftwareVersionService $version
    ) {
        $this->alert = $alert;
        $this->settings = $settings;
        $this->version = $version;
    }

    /**
     * Return the admin index view.
     *
     * @return \Illuminate\View\View
     */
    public function getIndex()
    {
        return view('admin.index', ['version' => $this->version]);
    }

    /**
     * Return the admin settings view.
     *
     * @return \Illuminate\View\View
     */
    public function getSettings()
    {
        return view('admin.settings');
    }

    /**
     * Handle settings post request.
     *
     * @param \Pterodactyl\Http\Requests\Admin\BaseFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postSettings(BaseFormRequest $request)
    {
        $this->settings->set('company', $request->input('company'));
        $this->settings->set('2fa', $request->input('2fa'));

        $this->alert->success('Settings have been successfully updated.')->flash();

        return redirect()->route('admin.settings');
    }
}
