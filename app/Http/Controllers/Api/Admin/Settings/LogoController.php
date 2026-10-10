<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Settings\ClearLogoRequest;
use Pterodactyl\Http\Requests\Api\Admin\Settings\UploadLogoRequest;
use Pterodactyl\Services\Helpers\BrandingLogoService;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Settings', 'View and update global panel settings.')]
class LogoController extends AdminApiController
{
    #[Endpoint('Upload branding logo', 'Stores a validated logo for the favicon, login page, and navigation bar.')]
    #[ScribeResponse(['logo' => '/extension-files/branding/example.png'], description: 'Logo uploaded.')]
    public function store(UploadLogoRequest $request, BrandingLogoService $logo): JsonResponse
    {
        return new JsonResponse(['logo' => $logo->replace($request->upload())]);
    }

    #[Endpoint('Remove branding logo', 'Removes the configured branding logo and restores the default branding.')]
    #[ScribeResponse(status: 204, description: 'Logo removed.')]
    public function destroy(ClearLogoRequest $request, BrandingLogoService $logo): Response
    {
        $logo->clear();

        return $this->returnNoContent();
    }
}
