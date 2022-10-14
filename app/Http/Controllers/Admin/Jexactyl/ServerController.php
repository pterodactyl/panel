<?php

namespace Pterodactyl\Http\Controllers\Admin\Jexactyl;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\Jexactyl\ServerFormRequest;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ServerController extends Controller
{
    /**
     * StoreController constructor.
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
        $prefix = 'jexactyl::renewal:';
    
        return view('admin.jexactyl.server', [
            'enabled' => $this->settings->get($prefix . 'enabled', false),
            'default' => $this->settings->get($prefix . 'default', 7),
            'cost' => $this->settings->get($prefix . 'cost', 20),
            'editing' => $this->settings->get($prefix . 'editing', false),
            'deletion' => $this->settings->get($prefix . 'deletion', true),
        ]);
    }

    /**
     * Handle settings update.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(ServerFormRequest $request): RedirectResponse
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('jexactyl::renewal:' . $key, $value);
        }

        $this->alert->success('Jexactyl Server settings has been updated.')->flash();

        return redirect()->route('admin.jexactyl.server');
    }
}
