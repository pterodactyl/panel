<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Events\Server\Installed as ServerInstalled;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Pterodactyl\Http\Requests\Api\Remote\InstallationDataRequest;

class ServerInstallController extends Controller
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    private $eventDispatcher;

    /**
     * ServerInstallController constructor.
     */
    public function __construct(ServerRepository $repository, EventDispatcher $eventDispatcher)
    {
        $this->repository = $repository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Returns installation information for a server.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function index(Request $request, string $uuid)
    {
        $server = $this->repository->getByUuid($uuid);
        $egg = $server->egg;

        return JsonResponse::create([
            'container_image' => $egg->copy_script_container,
            'entrypoint' => $egg->copy_script_entry,
            'script' => $egg->copy_script_install,
        ]);
    }

    /**
     * Updates the installation state of a server.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(InstallationDataRequest $request, string $uuid)
    {
        $server = $this->repository->getByUuid($uuid);

        $status = $request->boolean('successful') ? null : Server::STATUS_INSTALL_FAILED;
        if ($server->status === Server::STATUS_SUSPENDED) {
            $status = Server::STATUS_SUSPENDED;
        }

        $this->repository->update($server->id, ['status' => $status], true, true);

        // If the server successfully installed, fire installed event.
        if ($status === null) {
            $this->eventDispatcher->dispatch(new ServerInstalled($server));
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
