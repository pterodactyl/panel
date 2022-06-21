<?php

namespace Pterodactyl\Http\Controllers\Admin\Jexactyl;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Jexactyl\RenewalFormRequest;

class RenewalController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    private $settings;

    /**
     * StoreController constructor.
     */
    public function __construct(
        AlertsMessageBag $alert,
        SettingsRepositoryInterface $settings
    ) {
        $this->alert = $alert;
        $this->settings = $settings;
    }

    /**
     * Render the Jexactyl settings interface.
     */
    public function index(): View
    {
        $prefix = 'jexactyl::renewal:';
    
        return view('admin.jexactyl.renewal', [
            'enabled' => $this->settings->get($prefix.'enabled', false),
            'default' => $this->settings->get($prefix.'default', 7),
            'cost' => $this->settings->get($prefix.'cost', 20),
        ]);
    }

    /**
     * Handle settings update.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(RenewalFormRequest $request): RedirectResponse
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('jexactyl::renewal:' . $key, $value);
        }

        $this->alert->success('Jexactyl Renewal System has been updated.')->flash();

        return redirect()->route('admin.jexactyl.renewal');
    }
}
