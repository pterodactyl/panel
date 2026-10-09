<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\DatabaseHosts;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\DatabaseHost;

class UpdateDatabaseHostRequest extends StoreDatabaseHostRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminDatabaseHostsUpdate];
    }

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): DatabaseHost
    {
        return $this->parameter('databaseHost', DatabaseHost::class);
    }
}
