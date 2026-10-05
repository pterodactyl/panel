<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Extensions;

interface RemovesExtensions
{
    public function remove(string $identifier): void;
}
