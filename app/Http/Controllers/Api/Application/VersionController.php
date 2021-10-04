<?php

namespace Pterodactyl\Http\Controllers\Api\Application;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Services\Helpers\SoftwareVersionService;

class VersionController extends ApplicationApiController
{
    private SoftwareVersionService $softwareVersionService;

    /**
     * VersionController constructor.
     */
    public function __construct(SoftwareVersionService $softwareVersionService)
    {
        parent::__construct();

        $this->softwareVersionService = $softwareVersionService;
    }

    /**
     * Returns version information.
     */
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->softwareVersionService->getVersionData());
    }
}
