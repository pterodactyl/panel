<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Themes;

interface ResetsThemes
{
    /** Make the built-in classic theme active again. */
    public function reset(): void;
}
