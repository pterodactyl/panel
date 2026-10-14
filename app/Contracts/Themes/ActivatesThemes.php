<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Themes;

use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;

interface ActivatesThemes
{
    /**
     * Make the theme the one every visitor sees. Activating "classic" resets the panel.
     *
     * @throws InvalidExtensionException when the theme, or a theme it extends, is not installed and enabled
     */
    public function activate(string $id): void;
}
