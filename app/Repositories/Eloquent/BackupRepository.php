<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Backup;

class BackupRepository extends EloquentRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Backup::class;
    }
}
