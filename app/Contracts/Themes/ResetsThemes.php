<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Themes;

interface ResetsThemes
{
    public function reset(): void;
}
