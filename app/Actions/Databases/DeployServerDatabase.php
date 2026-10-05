<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Databases;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Pterodactyl\Contracts\Databases\CreatesDatabases;
use Pterodactyl\Contracts\Databases\DeploysServerDatabases;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException;
use Pterodactyl\Exceptions\Service\Database\NoSuitableDatabaseHostException;
use Pterodactyl\Exceptions\Service\Database\TooManyDatabasesException;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\DatabaseName;
use Throwable;

final readonly class DeployServerDatabase implements DeploysServerDatabases
{
    public function __construct(private CreatesDatabases $createDatabase) {}

    /** @param DatabaseDeploymentData $data
     * @throws Throwable
     * @throws TooManyDatabasesException
     * @throws DatabaseClientFeatureNotEnabledException
     * @throws NoSuitableDatabaseHostException
     * @throws DisplayException
     */
    public function deploy(Server $server, array $data): Database
    {
        return DB::transaction(function () use ($server, $data): Database {
            throw_if(empty($data['database']), InvalidArgumentException::class, 'Expected a non-empty database name.');
            throw_if(empty($data['remote']), InvalidArgumentException::class, 'Expected a non-empty remote database host.');
            throw_if($server->databases()->lockForUpdate()->count() >= $server->database_limit, DisplayException::class, 'Cannot create additional databases on this server: limit has been reached.');

            $hosts = DatabaseHost::query()->get()->toBase();
            throw_if($hosts->isEmpty(), NoSuitableDatabaseHostException::class);

            $nodeHosts = $hosts->where('node_id', $server->node_id)->toBase();
            throw_if($nodeHosts->isEmpty() && ! config('pterodactyl.client_features.databases.allow_random'), NoSuitableDatabaseHostException::class);

            $hostId = $data['database_host_id'] ?? null;

            return $this->createDatabase->create($server, [
                'database_host_id' => $hostId
                    ? (int) $hostId
                    : ($nodeHosts->isEmpty() ? $hosts->random()->id : $nodeHosts->random()->id),
                'database' => DatabaseName::generateUnique($data['database'], $server->id),
                'remote' => $data['remote'],
                'max_connections' => $data['max_connections'] ?? null,
            ]);
        });
    }
}
