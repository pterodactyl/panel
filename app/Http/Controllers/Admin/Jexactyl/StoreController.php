<?php

namespace Pterodactyl\Http\Controllers\Admin\Jexactyl;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Console\Kernel;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Jexactyl\JexactylStoreFormRequest;

class StoreController extends Controller
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
     * StoreController constructor.
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
     * Render the Jexactyl store settings interface.
     */
    public function index(): View
    {
        $prefix = 'jexactyl::store:';

        return view('admin.jexactyl.store', [
            'enabled' => $this->settings->get($prefix.'enabled', false),
            'paypal_enabled' => $this->settings->get($prefix.'paypal:enabled', false),
            'stripe_enabled' => $this->settings->get($prefix.'stripe:enabled', false),
            'cpu' => $this->settings->get($prefix.'cost:cpu', 100),
            'memory' => $this->settings->get($prefix.'cost:memory', 50),
            'disk' => $this->settings->get($prefix.'cost:disk', 25),
            'slot' => $this->settings->get($prefix.'cost:slot', 250),
            'port' => $this->settings->get($prefix.'cost:port', 20),
            'backup' => $this->settings->get($prefix.'cost:backup', 20),
            'database' => $this->settings->get($prefix.'cost:database', 20),
        ]);
    }

    /**
     * Handle settings update.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(JexactylStoreFormRequest $request): RedirectResponse
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('jexactyl::' . $key, $value);
        }

        $this->kernel->call('queue:restart');
        $this->alert->warning('If you have enabled a payment gateway, please remember to configure them. <a href="https://documentation.jexactyl.com">Documentation</a>')->flash();
        $this->alert->success('Jexactyl Storefront has been updated.')->flash();

        return redirect()->route('admin.jexactyl.store');
    }
}
