<?php

namespace Pterodactyl\Http\Controllers\Admin\Jexactyl;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Exceptions\Model\DataValidationException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Jexactyl\AppearanceFormRequest;

class AppearanceController extends Controller
{
    /**
     * AppearanceController constructor.
     */
    public function __construct(
        private Repository $config,
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * Render the Jexactyl settings interface.
     */
    public function index(): View
    {
        return view('admin.jexactyl.appearance', [
            'name' => config('app.name'),
            'logo' => config('app.logo'),

            'admin' => config('theme.admin'),
            'user' => ['background' => config('theme.user.background')],
        ]);
    }

    /**
     * Handle settings update.
     *
     * @throws DataValidationException|RecordNotFoundException
     */
    public function update(AppearanceFormRequest $request): RedirectResponse
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::' . $key, $value);
        }

        $this->alert->success('Jexactyl Appearance has been updated.')->flash();

        return redirect()->route('admin.jexactyl.appearance');
    }
}
