<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
