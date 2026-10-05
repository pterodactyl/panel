<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Extensions;

use Pterodactyl\Contracts\Extensions\UpdatesExtensionSettings;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;

final readonly class UpdateExtensionSettings implements UpdatesExtensionSettings
{
    /**
     * @param  ExtensionSettingValues  $settings
     * @return list<ExtensionSettingField>
     */
    public function update(ExtensionSettingsDefinition $definition, array $settings): array
    {
        $definition->update($settings);

        return $definition->schema();
    }
}
