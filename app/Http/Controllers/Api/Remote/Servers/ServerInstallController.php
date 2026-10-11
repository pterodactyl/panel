<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Contracts\Servers\RecordsServerInstallResults;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Api\Remote\InstallationDataRequest;
use Pterodactyl\Services\Servers\ServerInstallService;

class ServerInstallController extends Controller
{
    /**
     * Returns installation information for a server.
     */
    public function index(Request $request, ServerInstallService $install, string $uuid): JsonResponse
    {
        return new JsonResponse($install->info($request, $uuid));
    }

    /**
     * Updates the installation state of a server.
     */
    public function store(InstallationDataRequest $request, RecordsServerInstallResults $installResult, string $uuid): JsonResponse
    {
        $installResult->record($request, $uuid, $request->boolean('successful'), $request->boolean('reinstall'));

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
