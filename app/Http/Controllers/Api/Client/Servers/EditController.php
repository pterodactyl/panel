<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Servers\ServerEditService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\EditServerRequest;

class EditController extends ClientApiController
{
    private ServerEditService $editService;

    /**
     * PowerController constructor.
     */
    public function __construct(ServerEditService $editService)
    {
        parent::__construct();

        $this->editService = $editService;
    }

    /**
     * Edit a server's resource limits.
     *
     * @throws DisplayException
     */
    public function index(EditServerRequest $request, Server $server): JsonResponse
    {
       $this->editService->handle($request, $server);

       return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
