<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Extensions;

use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Services\Extensions\Contracts\ManagesExtensionSettings;
use Pterodactyl\Services\Extensions\ExtensionSettingValueGuard;
use Pterodactyl\Support\JsonValueGuard;

abstract class ExtensionAdminSettingsController extends AdminApiController
{
    abstract protected function extensionSettings(): ManagesExtensionSettings;

    /**
     * @param  ApiPayload9  $extra
     * @return ApiPayload
     */
    protected function settingsPayload(array $extra = []): array
    {
        $payload = [
            ...$extra,
            'settings' => $this->extensionSettings()->publicSettings(),
        ];
        JsonValueGuard::assertPayload($payload);

        return $payload;
    }

    /** @return ApiPayload */
    protected function updateExtensionSettings(Request $request): array
    {
        $data = $request->validate($this->extensionSettings()->validationRules());
        ExtensionSettingValueGuard::assertValues($data);

        $this->extensionSettings()->update($data);

        return $data;
    }
}
