<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Extensions;

use Illuminate\Http\UploadedFile;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;

interface ReplacesExtensionSettingFiles
{
    /**
     * Store an upload as the value of a file setting, or clear the setting when
     * $upload is null. The file it replaces is deleted.
     *
     * @return list<ExtensionSettingField>
     */
    public function replace(ExtensionSettingsDefinition $definition, string $input, ?UploadedFile $upload): array;
}
