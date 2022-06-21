<?php

namespace Pterodactyl\Http\Controllers\Admin\Jexactyl;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\Jexactyl\ThemeFormRequest;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ThemeController extends Controller
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
     * ThemeController constructor.
     */
    public function __construct(
        SettingsRepositoryInterface $settings,
        AlertsMessageBag $alert
    ) 
    {
        $this->alert = $alert;
        $this->settings = $settings;
    }

    /**
     * Render the Jexactyl settings interface.
     */
    public function index(): View
    {
        return view('admin.jexactyl.theme', [
            'current' => $this->settings->get('jexactyl::theme:current', 'default'),
        ]);
    }

    /**
     * Handle settings update.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(ThemeFormRequest $request): RedirectResponse
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('jexactyl::' . $key, $value);
        }

        $this->alert->success('Jexactyl Theme has been updated. Please run <code>yarn build</code> on your machine to update.')->flash();

        return redirect()->route('admin.jexactyl.theme');
    }
}
