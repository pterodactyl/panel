<?php

declare(strict_types=1);

namespace Pterodactyl\Traits\Helpers;

use Pterodactyl\Services\Translations\LanguageRegistry;

trait AvailableLanguages
{
    /**
     * @return array<string, string>
     */
    public function getAvailableLanguages(bool $localize = false): array
    {
        return resolve(LanguageRegistry::class)->available($localize);
    }
}
