<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Http\Requests\Api\Remote\InstallationDataRequest;

class ServerInstallController extends Controller
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * ServerInstallController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $repository
     */
    public function __construct(ServerRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Returns installation information for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $uuid
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
     * @param \Pterodactyl\Http\Requests\Api\Remote\InstallationDataRequest $request
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(InstallationDataRequest $request, string $uuid)
    {
        $server = $this->repository->getByUuid($uuid);

        $status = $request->input('successful') === '1' ? null : Server::STATUS_INSTALL_FAILED;
        if ($server->status === Server::STATUS_SUSPENDED) {
            $status = Server::STATUS_SUSPENDED;
        }

        $this->repository->update($server->id, ['status' => $status], true, true);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
