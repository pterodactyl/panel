<?php

namespace Pterodactyl\Http\Controllers\Server\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Services\Servers\ReinstallServerService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Http\Requests\Server\Settings\ChangeServerNameRequest;

class SettingsController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Services\Servers\ReinstallServerService
     */
    protected $reinstallService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * NameController constructor.
     *
     * @param AlertsMessageBag $alert
     * @param ReinstallServerService $reinstallService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(
        AlertsMessageBag $alert,
        ReinstallServerService $reinstallService,
        ServerRepositoryInterface $repository
    )
    {
        $this->alert = $alert;
        $this->reinstallService = $reinstallService;
        $this->repository = $repository;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('view-name', $request->attributes->get('server'));
        $this->setRequest($request)->injectJavascript();

        return view('server.settings');
    }

    /**
     * Update the stored name for a specific server.
     *
     * @param \Pterodactyl\Http\Requests\Server\Settings\ChangeServerNameRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(ChangeServerNameRequest $request): RedirectResponse
    {
        $this->repository->update($request->getServer()->id, $request->validated());

        return redirect()->route('server.settings', $request->getServer()->uuidShort);
    }

    /**
     * Reinstall the specific server.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function reinstallServer(Request $request): RedirectResponse
    {
        $this->authorize('reinstall-server', $request->attributes->get('server'));
        $this->reinstallService->reinstall($request->attributes->get('server'));
        $this->alert->success(trans('config.settings.reinstall.queued'))->flash();

        return redirect()->route('index');
    }
}
