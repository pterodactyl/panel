<?php

namespace Pterodactyl\Http\Controllers\Admin\Jexactyl;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Console\Kernel;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Traits\Helpers\AvailableLanguages;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Jexactyl\JexactylFormRequest;

class IndexController extends Controller
{
    use AvailableLanguages;

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
     * @var \Pterodactyl\Services\Helpers\SoftwareVersionService
     */
    private $versionService;

    /**
     * IndexController constructor.
     */
    public function __construct(
        AlertsMessageBag $alert,
        Kernel $kernel,
        SettingsRepositoryInterface $settings,
        SoftwareVersionService $versionService
    ) {
        $this->alert = $alert;
        $this->kernel = $kernel;
        $this->settings = $settings;
        $this->versionService = $versionService;
    }

    /**
     * Render the Jexactyl settings interface.
     */
    public function index(): View
    {
        $prefix = 'jexactyl::store:';

        return view('admin.jexactyl.index', [
            'version' => $this->versionService,
            'enabled' => $this->settings->get($prefix.'enabled', true),
            'cpu' => $this->settings->get($prefix.'cost:cpu'),
            'memory' => $this->settings->get($prefix.'cost:memory'),
            'disk' => $this->settings->get($prefix.'cost:disk'),
            'slot' => $this->settings->get($prefix.'cost:slot'),
            'port' => $this->settings->get($prefix.'cost:port'),
            'backup' => $this->settings->get($prefix.'cost:backup'),
            'database' => $this->settings->get($prefix.'cost:database'),
        ]);
    }

    /**
     * Handle settings update.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(JexactylFormRequest $request): RedirectResponse
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('jexactyl::' . $key, $value);
        }

        $this->kernel->call('queue:restart');
        $this->alert->success('Jexactyl settings have been updated.')->flash();

        return redirect()->route('admin.jexactyl');
    }
}
