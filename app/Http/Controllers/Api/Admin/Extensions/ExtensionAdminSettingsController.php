<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Extensions;

use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Services\Extensions\Contracts\ManagesExtensionSettings;
use Pterodactyl\Services\Extensions\ExtensionSettingValueGuard;

abstract class ExtensionAdminSettingsController extends AdminApiController
{
    abstract protected function extensionSettings(): ManagesExtensionSettings;

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function settingsPayload(array $extra = []): array
    {
        return [
            ...$extra,
            'settings' => $this->extensionSettings()->publicSettings(),
        ];
    }

    /** @return array<string, mixed> */
    protected function updateExtensionSettings(Request $request): array
    {
        $data = $request->validate($this->extensionSettings()->validationRules());
        ExtensionSettingValueGuard::assertValues($data);

        $this->extensionSettings()->update($data);

        return $data;
    }
}
