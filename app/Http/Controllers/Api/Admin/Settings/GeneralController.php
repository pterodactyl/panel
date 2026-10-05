<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Settings;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Settings\UpdateGeneralSettingsRequest;
use Pterodactyl\Models\Setting;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Settings', 'View and update global panel settings.')]
class GeneralController extends AdminApiController
{
    /**
     * Update general settings.
     */
    #[Endpoint('Update general settings', 'Updates panel branding, 2FA requirements, and default locale settings.')]
    #[ScribeResponse(status: 204, description: 'General settings updated.')]
    public function __invoke(UpdateGeneralSettingsRequest $request, Kernel $kernel): Response
    {
        foreach ($request->normalize() as $key => $value) {
            // SAFETY: settings are persisted in the environment store as strings.
            Setting::put('settings::'.$key, (string) $value);
        }

        $kernel->call('queue:restart');

        return $this->returnNoContent();
    }
}
