<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Eggs\EggConfigurationService;
use Pterodactyl\Repositories\Eloquent\ServerRepository;

class ServerConfigurationController extends Controller
{
    /**
     * @var \Pterodactyl\Services\Eggs\EggConfigurationService
     */
    private $eggConfigurationService;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * ServerConfigurationController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $repository
     * @param \Pterodactyl\Services\Eggs\EggConfigurationService $eggConfigurationService
     */
    public function __construct(ServerRepository $repository, EggConfigurationService $eggConfigurationService)
    {
        $this->eggConfigurationService = $eggConfigurationService;
        $this->repository = $repository;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $uuid
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function __invoke(Request $request, $uuid)
    {
        $server = $this->repository->getByUuid($uuid);

        return JsonResponse::create(
            $this->eggConfigurationService->handle($server)
        );
    }
}
