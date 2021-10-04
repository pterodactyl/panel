<?php

namespace Pterodactyl\Http\Controllers\Admin\Servers;

use JavaScript;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\NestRepository;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Pterodactyl\Http\Requests\Admin\ServerFormRequest;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Repositories\Eloquent\LocationRepository;

class CreateServerController extends Controller
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\NodeRepository
     */
    private $nodeRepository;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\NestRepository
     */
    private $nestRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\LocationRepository
     */
    private $locationRepository;

    /**
     * @var \Pterodactyl\Services\Servers\ServerCreationService
     */
    private $creationService;

    /**
     * CreateServerController constructor.
     */
    public function __construct(
        AlertsMessageBag $alert,
        NestRepository $nestRepository,
        LocationRepository $locationRepository,
        NodeRepository $nodeRepository,
        ServerRepository $repository,
        ServerCreationService $creationService
    ) {
        $this->repository = $repository;
        $this->nodeRepository = $nodeRepository;
        $this->alert = $alert;
        $this->nestRepository = $nestRepository;
        $this->locationRepository = $locationRepository;
        $this->creationService = $creationService;
    }

    /**
     * Displays the create server page.
     *
     * @return \Illuminate\Contracts\View\Factory
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function index()
    {
        $nodes = $this->nodeRepository->all();
        if (count($nodes) < 1) {
            $this->alert->warning(trans('admin/server.alerts.node_required'))->flash();

            return redirect()->route('admin.nodes');
        }

        $nests = $this->nestRepository->getWithEggs();

        Javascript::put([
            'nodeData' => $this->nodeRepository->getNodesForServerCreation(),
            'nests' => $nests->map(function ($item) {
                return array_merge($item->toArray(), [
                    'eggs' => $item->eggs->keyBy('id')->toArray(),
                ]);
            })->keyBy('id'),
        ]);

        return view('admin.servers.new', [
            'locations' => $this->locationRepository->all(),
            'nests' => $nests,
        ]);
    }

    /**
     * Create a new server on the remote system.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException
     * @throws \Throwable
     */
    public function store(ServerFormRequest $request)
    {
        $data = $request->except(['_token']);
        if (!empty($data['custom_image'])) {
            $data['image'] = $data['custom_image'];
            unset($data['custom_image']);
        }

        $server = $this->creationService->handle($data);

        $this->alert->success(
            trans('admin/server.alerts.server_created')
        )->flash();

        return RedirectResponse::create('/admin/servers/view/' . $server->id);
    }
}
