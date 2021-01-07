<?php

namespace Pterodactyl\Http\Controllers\Api\Application;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Services\Helpers\SoftwareVersionService;

class VersionController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Services\Helpers\SoftwareVersionService
     */
    private SoftwareVersionService $softwareVersionService;

    /**
     * VersionController constructor.
     *
     * @param \Pterodactyl\Services\Helpers\SoftwareVersionService $softwareVersionService
     */
    public function __construct(SoftwareVersionService $softwareVersionService)
    {
        parent::__construct();

        $this->softwareVersionService = $softwareVersionService;
    }

    /**
     * Returns version information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->softwareVersionService->getVersionData());
    }
}
