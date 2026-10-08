<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Mounts;

use Pterodactyl\Contracts\Mounts\UpdatesMounts;
use Pterodactyl\Models\Mount;

final readonly class UpdateMount implements UpdatesMounts
{
    /**
     * Update an existing mount from validated attributes.
     *
     * @param  MountData  $data
     */
    public function update(Mount $mount, array $data): Mount
    {
        $mount->forceFill($data)->save();

        return $mount;
    }
}
