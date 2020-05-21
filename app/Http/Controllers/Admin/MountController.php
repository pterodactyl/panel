<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\MountFormRequest;
use Pterodactyl\Services\Mounts\MountCreationService;
use Pterodactyl\Repositories\Eloquent\MountRepository;

class MountController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\MountRepository
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Locations\LocationCreationService
     */
    protected $creationService;

    /**
     * MountController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag $alert
     * @param \Pterodactyl\Repositories\Eloquent\MountRepository $repository
     * @param \Pterodactyl\Services\Mounts\MountCreationService $creationService
     */
    public function __construct(
        AlertsMessageBag $alert,
        MountRepository $repository,
        MountCreationService $creationService
    ) {
        $this->alert = $alert;
        $this->repository = $repository;
        $this->creationService = $creationService;
    }

    /**
     * Return the mount overview page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.mounts.index', [
            'mounts' => $this->repository->getAllWithDetails(),
        ]);
    }

    /**
     * Return the mount view page.
     *
     * @param string $id
     * @return \Illuminate\View\View
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function view($id)
    {
        return view('admin.mounts.view', [
            'mount' => $this->repository->getWithRelations($id),
        ]);
    }

    /**
     * Handle request to create new mount.
     *
     * @param \Pterodactyl\Http\Requests\Admin\MountFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function create(MountFormRequest $request)
    {
        $mount = $this->creationService->handle($request->normalize());
        $this->alert->success('Mount was created successfully.')->flash();

        //return redirect()->route('admin.mounts.view', $mount->id);
        return redirect()->route('admin.mounts');
    }
}
