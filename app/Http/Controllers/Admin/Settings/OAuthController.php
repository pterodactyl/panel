<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\Response;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Console\Kernel;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Settings\OAuthSettingsFormRequest;

class OAuthController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Illuminate\Contracts\Console\Kernel
     */
    private $kernel;

    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    private $settings;

    /**
     * IndexController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag $alert
     * @param \Illuminate\Contracts\Console\Kernel $kernel
     * @param \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface $settings
     */
    public function __construct(
        AlertsMessageBag $alert,
        Kernel $kernel,
        SettingsRepositoryInterface $settings)
    {
        $this->alert = $alert;
        $this->kernel = $kernel;
        $this->settings = $settings;
    }

    /**
     * Render the UI for basic Panel settings.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {

        // Don't send the client_secret
        $drivers = json_decode(app('config')->get('pterodactyl.auth.oauth.drivers'), true);

        foreach ($drivers as $driver => $options) {
            unset($drivers[$driver]['client_secret']);
        }

        return view('admin.settings.oauth', [
            'drivers' => json_encode($drivers)
        ]);
    }

    /**
     * Handle settings update.
     *
     * @param \Pterodactyl\Http\Requests\Admin\Settings\OAuthSettingsFormRequest $request
     * @return \Illuminate\Http\Response
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(OAuthSettingsFormRequest $request): Response
    {
        // Set current client_secret if empty
        $newDrivers = json_decode($request->normalize()['pterodactyl:auth:oauth:drivers'], true);
        $currentDrivers = json_decode(app('config')->get('pterodactyl.auth.oauth.drivers'), true);

        foreach ($newDrivers as $driver => $options) {
            if (!array_has($options, 'client_secret') || empty($options['client_secret'])) {
                $newDrivers[$driver]['client_secret'] = $currentDrivers[$driver]['client_secret'];
            }
        }

        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::' . $key, $key == 'pterodactyl:auth:oauth:drivers' ? json_encode($newDrivers) : $value);
        }

        $this->kernel->call('queue:restart');

        return response('', 204);
    }
}
