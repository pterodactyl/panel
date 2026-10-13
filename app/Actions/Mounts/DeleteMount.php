<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Mounts;

use Pterodactyl\Contracts\Mounts\DeletesMounts;
use Pterodactyl\Models\Mount;

final readonly class DeleteMount implements DeletesMounts
{
    public function delete(Mount $mount): void
    {
        $mount->delete();
    }
}
