<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Themes;

interface AppliesThemes
{
    /** Publish the theme's tokens.css, and its assets/ directory when it has one. */
    public function apply(string $id): void;
}
