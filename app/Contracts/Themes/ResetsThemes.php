<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Themes;

interface ResetsThemes
{
    /** Remove the published theme, restoring the stock look. */
    public function reset(): void;
}
