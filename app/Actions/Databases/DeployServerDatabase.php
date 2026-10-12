<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Databases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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

    /**
     * The fields of a database to deploy. The name is the one a user picks, before the
     * "s{server_id}_" prefix is added; with no host given, one is picked for the server.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        $rules = CreateDatabase::rules();

        return [
            'database' => ['required', 'alpha_dash', 'min:3', 'max:48'],
            'remote' => $rules['remote'],
            'database_host_id' => ['nullable', 'integer', 'exists:database_hosts,id'],
            'max_connections' => $rules['max_connections'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws Throwable
     * @throws TooManyDatabasesException
     * @throws DatabaseClientFeatureNotEnabledException
     * @throws NoSuitableDatabaseHostException
     * @throws DisplayException
     */
    public function deploy(Server $server, array $input): Database
    {
        $validated = Validator::make($input, self::rules())->safe();

        return DB::transaction(function () use ($server, $validated): Database {
            throw_if($server->databases()->lockForUpdate()->count() >= $server->database_limit, DisplayException::class, 'Cannot create additional databases on this server: limit has been reached.');

            $hosts = DatabaseHost::query()->get()->toBase();
            throw_if($hosts->isEmpty(), NoSuitableDatabaseHostException::class);

            $nodeHosts = $hosts->where('node_id', $server->node_id)->toBase();
            throw_if($nodeHosts->isEmpty() && ! config('pterodactyl.client_features.databases.allow_random'), NoSuitableDatabaseHostException::class);

            // A database given no connection limit gets none.
            return $this->createDatabase->create($server, [
                'database_host_id' => $validated->filled('database_host_id') ? $validated['database_host_id'] : ($nodeHosts->isEmpty() ? $hosts->random()->id : $nodeHosts->random()->id),
                'database' => DatabaseName::generateUnique($validated->string('database')->toString(), $server->id),
                'remote' => $validated['remote'],
                'max_connections' => $validated->filled('max_connections') ? $validated['max_connections'] : null,
            ]);
        });
    }
}
