<?php

namespace Pterodactyl\Http\Controllers\Admin\Jexactyl;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Console\Kernel;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Jexactyl\JexactylRegistrationFormRequest;

class RegistrationController extends Controller
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
     * RegistrationController constructor.
     */
    public function __construct(
        AlertsMessageBag $alert,
        Kernel $kernel,
        SettingsRepositoryInterface $settings,
    ) {
        $this->alert = $alert;
        $this->kernel = $kernel;
        $this->settings = $settings;
    }

    /**
     * Render the Jexactyl settings interface.
     */
    public function index(): View
    {
        $prefix = 'jexactyl::registration:';

        return view('admin.jexactyl.registration', [
            'enabled' => $this->settings->get('jexactyl::registration:enabled', false),
            'cpu' => $this->settings->get($prefix.'cpu', 100),
            'memory' => $this->settings->get($prefix.'memory', 1024),
            'disk' => $this->settings->get($prefix.'disk', 5120),
            'slot' => $this->settings->get($prefix.'slot', 1),
            'port' => $this->settings->get($prefix.'port', 1),
            'backup' => $this->settings->get($prefix.'backup', 1),
            'database' => $this->settings->get($prefix.'database', 0),
        ]);
    }

    /**
     * Handle settings update.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(JexactylRegistrationFormRequest $request): RedirectResponse
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('jexactyl::' . $key, $value);
        }

        $this->kernel->call('queue:restart');
        $this->alert->success('Jexactyl Registration has been updated.')->flash();

        return redirect()->route('admin.jexactyl.registration');
    }
}
