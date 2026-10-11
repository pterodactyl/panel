<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions\Contracts;

interface ManagesExtensionSettings
{
    /** @return array<string, mixed> */
    public function publicSettings(): array;

    /** @return NormalizedValidationRules */
    public function validationRules(): array;

    /** @param array<string, mixed> $settings */
    public function update(array $settings): void;
}
