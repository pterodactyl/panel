<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Backups;

use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UnexpectedValueException;

class ViewBackupRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $server = $this->parameter('server', Server::class);
        $route = $this->route();
        throw_if($route === null, UnexpectedValueException::class, 'The backup request does not have an active route.');

        $backup = $route->parameter('backup');
        if ($backup instanceof Backup) {
            throw_if($backup->server_id !== $server->id, NotFoundHttpException::class, 'The requested resource does not exist on the system.');
        }

        return true;
    }

    public function permission(): string
    {
        return Permissions::BackupRead->value;
    }
}
