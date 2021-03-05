<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Services\Servers\ReinstallServerService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Pterodactyl\Http\Requests\Api\Client\Servers\Settings\RenameServerRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Settings\SetDockerImageRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Settings\ReinstallServerRequest;

class SettingsController extends ClientApiController
{
    private ServerRepository $repository;
    private ReinstallServerService $reinstallServerService;

    /**
     * SettingsController constructor.
     */
    public function __construct(
        ServerRepository $repository,
        ReinstallServerService $reinstallServerService
    ) {
        parent::__construct();

        $this->repository = $repository;
        $this->reinstallServerService = $reinstallServerService;
    }

    /**
     * Renames a server.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function rename(RenameServerRequest $request, Server $server): Response
    {
        $this->repository->update($server->id, [
            'name' => $request->input('name'),
        ]);

        return $this->returnNoContent();
    }

    /**
     * Reinstalls the server on the daemon.
     *
     * @throws \Throwable
     */
    public function reinstall(ReinstallServerRequest $request, Server $server): Response
    {
        $this->reinstallServerService->handle($server);

        return $this->returnAccepted();
    }

    /**
     * Changes the Docker image in use by the server.
     *
     * @throws \Throwable
     */
    public function dockerImage(SetDockerImageRequest $request, Server $server): Response
    {
        if (!in_array($server->image, $server->egg->docker_images)) {
            throw new BadRequestHttpException('This server\'s Docker image has been manually set by an administrator and cannot be updated.');
        }

        $server->forceFill(['image' => $request->input('docker_image')])->saveOrFail();

        return $this->returnNoContent();
    }
}
