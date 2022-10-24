<?php

namespace Pterodactyl\Services\Nests;

use Pterodactyl\Models\Nest;

class NestUpdateService
{
    /**
     * Update a nest and prevent changing the author once it is set.
     *
     */
    public function handle(int $nest, array $data): void
    {
        if (!is_null(array_get($data, 'author'))) {
            unset($data['author']);
        }

        $nest = Nest::query()->findOrFail($nest);
        $nest->update($data);
    }
}
