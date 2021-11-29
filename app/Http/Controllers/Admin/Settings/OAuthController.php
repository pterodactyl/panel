<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\Response;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Console\Kernel;
use Pterodactyl\Http\Controllers\Controller;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Pterodactyl\Exceptions\Model\DataValidationException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Settings\OAuthSettingsFormRequest;

class OAuthController extends Controller
{
    private AlertsMessageBag $alert;

    private Kernel $kernel;

    private SettingsRepositoryInterface $settings;

    /**
     * IndexController constructor.
     */
    public function __construct(
        AlertsMessageBag $alert,
        Kernel $kernel,
        SettingsRepositoryInterface $settings
    )
    {
        $this->alert = $alert;
        $this->kernel = $kernel;
        $this->settings = $settings;
    }

    /**
     * Render the UI for basic Panel settings.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(): View
    {
        // Don't send the client_secret
        $drivers = json_decode(app('config')->get('pterodactyl.auth.oauth.drivers'), true);

        info($drivers);

        foreach ($drivers as $driver => $options) {
            unset($drivers[$driver]['client_secret']);
        }

        return view('admin.settings.oauth', [
            'drivers' => json_encode($drivers),
        ]);
    }

    /**
     * Handle settings update.
     *
     * @throws DataValidationException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RecordNotFoundException
     */
    public function update(OAuthSettingsFormRequest $request): Response
    {
        // Set current client_secret if empty
        $newDrivers = json_decode($request->normalize()['oauth:drivers'], true);
        $currentDrivers = json_decode(app('config')->get('pterodactyl.auth.oauth.drivers'), true);

        foreach ($newDrivers as $driver => $options) {
            if (!array_has($options, 'client_secret') || empty($options['client_secret'])) {
                $newDrivers[$driver]['client_secret'] = $currentDrivers[$driver]['client_secret'];
            }
        }

        info(json_encode($newDrivers));
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::' . $key, $key == 'oauth:drivers' ? json_encode($newDrivers) : $value);
        }

        $this->kernel->call('queue:restart');

        return response('', 204);
    }
}
