<?php

namespace App\Http\Controllers\Server;

use Illuminate\View\View;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use App\Services\Subusers\SubuserUpdateService;
use App\Traits\Controllers\JavascriptInjection;
use App\Services\Subusers\SubuserCreationService;
use App\Services\Subusers\SubuserDeletionService;
use App\Contracts\Repository\SubuserRepositoryInterface;
use App\Http\Requests\Server\Subuser\SubuserStoreFormRequest;
use App\Http\Requests\Server\Subuser\SubuserUpdateFormRequest;

class SubuserController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \App\Contracts\Repository\SubuserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \App\Services\Subusers\SubuserCreationService
     */
    protected $subuserCreationService;

    /**
     * @var \App\Services\Subusers\SubuserDeletionService
     */
    protected $subuserDeletionService;

    /**
     * @var \App\Services\Subusers\SubuserUpdateService
     */
    protected $subuserUpdateService;

    /**
     * SubuserController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                            $alert
     * @param \App\Services\Subusers\SubuserCreationService        $subuserCreationService
     * @param \App\Services\Subusers\SubuserDeletionService        $subuserDeletionService
     * @param \App\Contracts\Repository\SubuserRepositoryInterface $repository
     * @param \App\Services\Subusers\SubuserUpdateService          $subuserUpdateService
     */
    public function __construct(
        AlertsMessageBag $alert,
        SubuserCreationService $subuserCreationService,
        SubuserDeletionService $subuserDeletionService,
        SubuserRepositoryInterface $repository,
        SubuserUpdateService $subuserUpdateService
    ) {
        $this->alert = $alert;
        $this->repository = $repository;
        $this->subuserCreationService = $subuserCreationService;
        $this->subuserDeletionService = $subuserDeletionService;
        $this->subuserUpdateService = $subuserUpdateService;
    }

    /**
     * Displays the subuser overview index.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request): View
    {
        $server = $request->attributes->get('server');
        $this->authorize('list-subusers', $server);
        $this->setRequest($request)->injectJavascript();

        return view('server.users.index', [
            'subusers' => $this->repository->findWhere([['server_id', '=', $server->id]]),
        ]);
    }

    /**
     * Displays a single subuser overview.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function view(Request $request): View
    {
        $server = $request->attributes->get('server');
        $this->authorize('view-subuser', $server);

        $subuser = $this->repository->getWithPermissions($request->attributes->get('subuser'));
        $this->setRequest($request)->injectJavascript();

        return view('server.users.view', [
            'subuser' => $subuser,
            'permlist' => Permission::getPermissions(),
            'permissions' => $subuser->getRelation('permissions')->mapWithKeys(function ($item) {
                return [$item->permission => true];
            }),
        ]);
    }

    /**
     * Handles editing a subuser.
     *
     * @param \App\Http\Requests\Server\Subuser\SubuserUpdateFormRequest $request
     * @param string                                                             $uuid
     * @param string                                                             $hash
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function update(SubuserUpdateFormRequest $request, string $uuid, string $hash): RedirectResponse
    {
        $this->subuserUpdateService->handle($request->attributes->get('subuser'), $request->input('permissions', []));
        $this->alert->success(trans('server.users.user_updated'))->flash();

        return redirect()->route('server.subusers.view', ['uuid' => $uuid, 'subuser' => $hash]);
    }

    /**
     * Display new subuser creation page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Request $request): View
    {
        $server = $request->attributes->get('server');
        $this->authorize('create-subuser', $server);
        $this->setRequest($request)->injectJavascript();

        return view('server.users.new', ['permissions' => Permission::getPermissions()]);
    }

    /**
     * Handles creating a new subuser.
     *
     * @param \App\Http\Requests\Server\Subuser\SubuserStoreFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\Subuser\ServerSubuserExistsException
     * @throws \App\Exceptions\Service\Subuser\UserIsServerOwnerException
     */
    public function store(SubuserStoreFormRequest $request): RedirectResponse
    {
        $server = $request->attributes->get('server');

        $subuser = $this->subuserCreationService->handle($server, $request->input('email'), $request->input('permissions', []));
        $this->alert->success(trans('server.users.user_assigned'))->flash();

        return redirect()->route('server.subusers.view', [
            'uuid' => $server->uuidShort,
            'id' => $subuser->hashid,
        ]);
    }

    /**
     * Handles deleting a subuser.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function delete(Request $request): Response
    {
        $server = $request->attributes->get('server');
        $this->authorize('delete-subuser', $server);

        $this->subuserDeletionService->handle($request->attributes->get('subuser'));

        return response('', 204);
    }
}
