<?php

namespace Pterodactyl\Http\Controllers\Server\Settings;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Services\Servers\StartupCommandViewService;
use Pterodactyl\Services\Servers\StartupModificationService;
use Pterodactyl\Http\Requests\Server\UpdateStartupParametersFormRequest;

class StartupController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Pterodactyl\Services\Servers\StartupCommandViewService
     */
    private $commandViewService;

    /**
     * @var \Pterodactyl\Services\Servers\StartupModificationService
     */
    private $modificationService;

    /**
     * StartupController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                        $alert
     * @param \Pterodactyl\Services\Servers\StartupCommandViewService  $commandViewService
     * @param \Pterodactyl\Services\Servers\StartupModificationService $modificationService
     */
    public function __construct(
        AlertsMessageBag $alert,
        StartupCommandViewService $commandViewService,
        StartupModificationService $modificationService
    ) {
        $this->alert = $alert;
        $this->commandViewService = $commandViewService;
        $this->modificationService = $modificationService;
    }

    /**
     * Render the server startup page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function index(Request $request): View
    {
        $server = $request->attributes->get('server');
        $this->authorize('view-startup', $server);
        $this->setRequest($request)->injectJavascript();

        $data = $this->commandViewService->handle($server->id);

        return view('server.settings.startup', [
            'variables' => $data->get('variables'),
            'server_values' => $data->get('server_values'),
            'startup' => $data->get('startup'),
        ]);
    }

    /**
     * Handle request to update the startup variables for a server. Authorization
     * is handled in the form request.
     *
     * @param \Pterodactyl\Http\Requests\Server\UpdateStartupParametersFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateStartupParametersFormRequest $request): RedirectResponse
    {
        $this->modificationService->setUserLevel(User::USER_LEVEL_USER);
        $this->modificationService->handle($request->attributes->get('server'), $request->normalize());
        $this->alert->success(trans('server.config.startup.edited'))->flash();

        return redirect()->route('server.settings.startup', ['server' => $request->attributes->get('server')->uuidShort]);
    }
}
