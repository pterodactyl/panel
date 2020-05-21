<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\Mount;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Mounts\MountUpdateService;
use Pterodactyl\Http\Requests\Admin\MountFormRequest;
use Pterodactyl\Services\Mounts\MountCreationService;
use Pterodactyl\Services\Mounts\MountDeletionService;
use Pterodactyl\Repositories\Eloquent\MountRepository;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class MountController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\NestRepositoryInterface
     */
    protected $nestRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\MountRepository
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Mounts\MountCreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Services\Mounts\MountDeletionService
     */
    protected $deletionService;

    /**
     * @var \Pterodactyl\Services\Mounts\MountUpdateService
     */
    protected $updateService;

    /**
     * MountController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag $alert
     * @param \Pterodactyl\Contracts\Repository\NestRepositoryInterface $nestRepository
     * @param \Pterodactyl\Contracts\Repository\LocationRepositoryInterface $locationRepository
     * @param \Pterodactyl\Repositories\Eloquent\MountRepository $repository
     * @param \Pterodactyl\Services\Mounts\MountCreationService $creationService
     * @param \Pterodactyl\Services\Mounts\MountDeletionService $deletionService
     * @param \Pterodactyl\Services\Mounts\MountUpdateService $updateService
     */
    public function __construct(
        AlertsMessageBag $alert,
        NestRepositoryInterface $nestRepository,
        LocationRepositoryInterface $locationRepository,
        MountRepository $repository,
        MountCreationService $creationService,
        MountDeletionService $deletionService,
        MountUpdateService $updateService
    ) {
        $this->alert = $alert;
        $this->nestRepository = $nestRepository;
        $this->locationRepository = $locationRepository;
        $this->repository = $repository;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->updateService = $updateService;
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
        $nests = $this->nestRepository->all();
        $nests->load('eggs');

        $locations = $this->locationRepository->all();
        $locations->load('nodes');

        return view('admin.mounts.view', [
            'mount' => $this->repository->getWithRelations($id),
            'nests' => $nests,
            'locations' => $locations,
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

        return redirect()->route('admin.mounts.view', $mount->id);
    }

    /**
     * Handle request to update or delete location.
     *
     * @param \Pterodactyl\Http\Requests\Admin\MountFormRequest $request
     * @param \Pterodactyl\Models\Mount $mount
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function update(MountFormRequest $request, Mount $mount)
    {
        if ($request->input('action') === 'delete') {
            return $this->delete($mount);
        }

        $this->updateService->handle($mount->id, $request->normalize());
        $this->alert->success('Mount was updated successfully.')->flash();

        return redirect()->route('admin.mounts.view', $mount->id);
    }

    /**
     * Delete a location from the system.
     *
     * @param \Pterodactyl\Models\Mount $mount
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     */
    public function delete(Mount $mount)
    {
        try {
            $this->deletionService->handle($mount->id);

            return redirect()->route('admin.mounts');
        } catch (DisplayException $ex) {
            $this->alert->danger($ex->getMessage())->flash();
        }

        return redirect()->route('admin.mounts.view', $mount->id);
    }

    /**
     * Adds eggs to the mount's many to many relation.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Mount $mount
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addEggs(Request $request, Mount $mount)
    {
        $validatedData = $request->validate([
            'eggs' => 'required|exists:eggs,id',
        ]);

        $eggs = $validatedData['eggs'] ?? [];
        if (sizeof($eggs) > 0) {
            $mount->eggs()->attach(array_map('intval', $eggs));
            $this->alert->success('Mount was updated successfully.')->flash();
        }

        return redirect()->route('admin.mounts.view', $mount->id);
    }

    /**
     * Adds nodes to the mount's many to many relation.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Mount $mount
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addNodes(Request $request, Mount $mount)
    {
        $validatedData = $request->validate([
            'nodes' => 'required|exists:nodes,id',
        ]);

        $nodes = $validatedData['nodes'] ?? [];
        if (sizeof($nodes) > 0) {
            $mount->nodes()->attach(array_map('intval', $nodes));
            $this->alert->success('Mount was updated successfully.')->flash();
        }

        return redirect()->route('admin.mounts.view', $mount->id);
    }

    /**
     * Deletes an egg from the mount's many to many relation.
     *
     * @param \Pterodactyl\Models\Mount $mount
     * @param int $egg_id
     * @return \Illuminate\Http\Response
     */
    public function deleteEgg(Mount $mount, int $egg_id)
    {
        $mount->eggs()->detach($egg_id);

        return response('', 204);
    }

    /**
     * Deletes an node from the mount's many to many relation.
     *
     * @param \Pterodactyl\Models\Mount $mount
     * @param int $node_id
     * @return \Illuminate\Http\Response
     */
    public function deleteNode(Mount $mount, int $node_id)
    {
        $mount->nodes()->detach($node_id);

        return response('', 204);
    }
}
