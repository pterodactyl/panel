<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Server;

use Illuminate\Http\Request;
use Pterodactyl\Models\Permission;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Session\Session;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Subusers\SubuserUpdateService;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Services\Subusers\SubuserCreationService;
use Pterodactyl\Services\Subusers\SubuserDeletionService;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;

class SubuserController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * @var \Pterodactyl\Services\Subusers\SubuserCreationService
     */
    protected $subuserCreationService;

    /**
     * @var \Pterodactyl\Services\Subusers\SubuserDeletionService
     */
    protected $subuserDeletionService;

    /**
     * @var \Pterodactyl\Services\Subusers\SubuserUpdateService
     */
    protected $subuserUpdateService;

    /**
     * SubuserController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                            $alert
     * @param \Illuminate\Contracts\Session\Session                        $session
     * @param \Pterodactyl\Services\Subusers\SubuserCreationService        $subuserCreationService
     * @param \Pterodactyl\Services\Subusers\SubuserDeletionService        $subuserDeletionService
     * @param \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface $repository
     * @param \Pterodactyl\Services\Subusers\SubuserUpdateService          $subuserUpdateService
     */
    public function __construct(
        AlertsMessageBag $alert,
        Session $session,
        SubuserCreationService $subuserCreationService,
        SubuserDeletionService $subuserDeletionService,
        SubuserRepositoryInterface $repository,
        SubuserUpdateService $subuserUpdateService
    ) {
        $this->alert = $alert;
        $this->repository = $repository;
        $this->session = $session;
        $this->subuserCreationService = $subuserCreationService;
        $this->subuserDeletionService = $subuserDeletionService;
        $this->subuserUpdateService = $subuserUpdateService;
    }

    /**
     * Displays the subuser overview index.
     *
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function index()
    {
        $server = $this->session->get('server_data.model');
        $this->authorize('list-subusers', $server);

        $this->injectJavascript();

        return view('server.users.index', [
            'subusers' => $this->repository->findWhere([['server_id', '=', $server->id]]),
        ]);
    }

    /**
     * Displays the a single subuser overview.
     *
     * @param string $uuid
     * @param int    $id
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function view($uuid, $id)
    {
        $server = $this->session->get('server_data.model');
        $this->authorize('view-subuser', $server);

        $subuser = $this->repository->getWithPermissions($id);
        $this->injectJavascript();

        return view('server.users.view', [
            'subuser' => $subuser,
            'permlist' => Permission::getPermissions(),
            'permissions' => $subuser->permissions->mapWithKeys(function ($item, $key) {
                return [$item->permission => true];
            }),
        ]);
    }

    /**
     * Handles editing a subuser.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
     * @param int                      $id
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(Request $request, $uuid, $id)
    {
        $server = $this->session->get('server_data.model');
        $this->authorize('edit-subuser', $server);

        $this->subuserUpdateService->handle($id, $request->input('permissions', []));
        $this->alert->success(trans('server.users.user_updated'))->flash();

        return redirect()->route('server.subusers.view', ['uuid' => $uuid, 'id' => $id]);
    }

    /**
     * Display new subuser creation page.
     *
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create()
    {
        $server = $this->session->get('server_data.model');
        $this->authorize('create-subuser', $server);

        $this->injectJavascript();

        return view('server.users.new', ['permissions' => Permission::getPermissions()]);
    }

    /**
     * Handles creating a new subuser.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Subuser\ServerSubuserExistsException
     * @throws \Pterodactyl\Exceptions\Service\Subuser\UserIsServerOwnerException
     */
    public function store(Request $request, $uuid)
    {
        $server = $this->session->get('server_data.model');
        $this->authorize('create-subuser', $server);

        $subuser = $this->subuserCreationService->handle($server, $request->input('email'), $request->input('permissions', []));
        $this->alert->success(trans('server.users.user_assigned'))->flash();

        return redirect()->route('server.subusers.view', [
            'uuid' => $uuid,
            'id' => $subuser->id,
        ]);
    }

    /**
     * Handles deleting a subuser.
     *
     * @param string $uuid
     * @param int    $id
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function delete($uuid, $id)
    {
        $server = $this->session->get('server_data.model');
        $this->authorize('delete-subuser', $server);

        $this->subuserDeletionService->handle($id);

        return response('', 204);
    }
}
