<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Databases\RotatesDatabasePasswords;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\Databases\ServerDatabaseWriteRequest;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Admin\ServerDatabaseTransformer;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Server Databases', 'Create, inspect, and delete databases assigned to a server.')]
class RotateDatabasePasswordController extends AdminApiController
{
    /**
     * Rotate server database password.
     *
     * @return ApiPayload
     */
    #[Endpoint('Rotate server database password', 'Rotates the password for a database assigned to a server.')]
    #[ResponseFromTransformer(ServerDatabaseTransformer::class, Database::class, description: 'Database password rotated.', resourceKey: 'server_database', include: ['password'])]
    public function __invoke(ServerDatabaseWriteRequest $request, RotatesDatabasePasswords $passwordService, Server $server, Database $database): array
    {
        if ($database->server_id !== $server->id) {
            throw (new ModelNotFoundException)->setModel(Database::class);
        }

        Activity::event('admin:server-database.rotate-password')
            ->subject($database)
            ->property('name', $database->database)
            ->transaction(fn (): string => $passwordService->rotate($database));

        return Fractal::item($database->refresh())
            ->parseIncludes(['password'])
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->toResponseArray();
    }
}
