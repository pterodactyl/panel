<?php

namespace Pterodactyl\Http\Controllers\Admin\Jexactyl;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Jexactyl\RegistrationFormRequest;

class RegistrationController extends Controller
{
    /**
     * RegistrationController constructor.
     */
    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * Render the Jexactyl settings interface.
     */
    public function index(): View
    {
        return view('admin.jexactyl.registration', [
            'enabled' => $this->settings->get('jexactyl::registration:enabled', false),

            'discord_enabled' => $this->settings->get('jexactyl::discord:enabled', false),
            'discord_id' => $this->settings->get('jexactyl::discord:id', 0),
            'discord_secret' => $this->settings->get('jexactyl::discord:secret', 0),

            'cpu' => $this->settings->get('jexactyl::registration:cpu', 100),
            'memory' => $this->settings->get('jexactyl::registration:memory', 1024),
            'disk' => $this->settings->get('jexactyl::registration:disk', 5120),
            'slot' => $this->settings->get('jexactyl::registration:slot', 1),
            'port' => $this->settings->get('jexactyl::registration:port', 1),
            'backup' => $this->settings->get('jexactyl::registration:backup', 1),
            'database' => $this->settings->get('jexactyl::registration:database', 0),
        ]);
    }

    /**
     * Handle settings update.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(RegistrationFormRequest $request): RedirectResponse
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('jexactyl::' . $key, $value);
        }

        $this->alert->success('Jexactyl Registration has been updated.')->flash();

        return redirect()->route('admin.jexactyl.registration');
    }
}
