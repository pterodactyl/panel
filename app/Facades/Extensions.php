<?php

declare(strict_types=1);

namespace Pterodactyl\Facades;

use Illuminate\Support\Facades\Facade;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Extensions\ExtensionSettings;

/**
 * The extension runtime for code outside a provider: an extension's typed settings, and
 * whether an extension is running right now.
 *
 * @method static ExtensionSettings settings(string $identifier)
 * @method static bool isAvailable(string $identifier)
 *
 * @see ExtensionRepository
 */
class Extensions extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ExtensionRepository::class;
    }
}
