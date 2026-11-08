<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Settings\ReplacesBrandLogo;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Settings\DeleteLogoRequest;
use Pterodactyl\Http\Requests\Api\Admin\Settings\UploadLogoRequest;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Settings', 'View and update global panel settings.')]
class LogoController extends AdminApiController
{
    /**
     * Upload the panel logo.
     */
    #[Endpoint('Upload logo', 'Stores the uploaded image as the panel logo, shown as the favicon and on the login page and navigation bar, and deletes the one it replaces.')]
    #[BodyParam('logo', 'file', 'PNG, JPEG, SVG, WebP, AVIF or ICO image of at most 10 MiB.', required: true)]
    #[ScribeResponse(['logo' => '/extension-files/panel-logo/0123456789abcdef0123456789abcdef01234567.png'], description: 'Logo uploaded.')]
    public function store(UploadLogoRequest $request, ReplacesBrandLogo $logo): JsonResponse
    {
        return new JsonResponse(['logo' => $logo->replace($request->logo())]);
    }

    /**
     * Delete the panel logo.
     */
    #[Endpoint('Delete logo', 'Deletes the panel logo, restoring the default branding.')]
    #[ScribeResponse(status: 204, description: 'Logo deleted.')]
    public function destroy(DeleteLogoRequest $request, ReplacesBrandLogo $logo): Response
    {
        $logo->replace(null);

        return $this->returnNoContent();
    }
}
