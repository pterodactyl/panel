<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Mounts;

use Pterodactyl\Contracts\Mounts\CreatesMounts;
use Pterodactyl\Models\Mount;
use Ramsey\Uuid\Uuid;

final readonly class CreateMount implements CreatesMounts
{
    /**
     * Create a new mount from validated attributes, assigning it a fresh UUID.
     *
     * @param  MountData  $data
     */
    public function create(array $data): Mount
    {
        $mount = (new Mount)->fill($data);
        $mount->forceFill(['uuid' => Uuid::uuid4()->toString()]);
        $mount->saveOrFail();

        return $mount;
    }
}
