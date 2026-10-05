<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Extensions;

use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;

interface UpdatesExtensionSettings
{
    /**
     * @param  ExtensionSettingValues  $settings
     * @return list<ExtensionSettingField>
     */
    public function update(ExtensionSettingsDefinition $definition, array $settings): array;
}
