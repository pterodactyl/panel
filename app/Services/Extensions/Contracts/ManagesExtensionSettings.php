<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions\Contracts;

interface ManagesExtensionSettings
{
    /** @return ExtensionSettingValues */
    public function publicSettings(): array;

    /** @return NormalizedValidationRules */
    public function validationRules(): array;

    /** @param ExtensionSettingValues $settings */
    public function update(array $settings): void;
}
