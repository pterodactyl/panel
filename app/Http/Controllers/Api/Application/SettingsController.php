<?php

namespace Pterodactyl\Http\Controllers\Api\Application;

use Pterodactyl\Services\Helpers\SettingsService;
use Pterodactyl\Transformers\Api\Application\SettingsTransformer;

class SettingsController extends ApplicationApiController
{
    /**
     * VersionController constructor.
     */
    public function __construct(private SettingsService $settingsService)
    {
        parent::__construct();
    }

    /**
     * Returns version information.
     */
    public function __invoke(): array
    {
        return $this->fractal->item($this->settingsService->getCurrentSettings())->transformWith(SettingsTransformer::class)->toArray();
    }
}
