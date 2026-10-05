<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Themes;

interface AppliesThemes
{
    public function apply(string $id): void;
}
