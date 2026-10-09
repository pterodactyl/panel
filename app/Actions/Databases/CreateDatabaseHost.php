<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Databases;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Databases\CreatesDatabaseHosts;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;
use Pterodactyl\Support\JsonValueGuard;
use Throwable;

final readonly class CreateDatabaseHost implements CreatesDatabaseHosts
{
    public function __construct(private DynamicDatabaseConnection $dynamic, private ExtensionFields $extensions) {}

    /**
     * Create a new database host on the Panel.
     *
     * @param  DatabaseHostUpdateData  $data
     *
     * @throws Throwable
     */
    public function create(array $data): DatabaseHost
    {
        $extensions = ValidatedExtensionValues::of($data['extensions'] ?? null);

        $host = DatabaseHost::query()->make([
            'password' => Crypt::encrypt(Arr::get($data, 'password')),
            'name' => JsonValueGuard::string(Arr::get($data, 'name')),
            'host' => JsonValueGuard::string(Arr::get($data, 'host')),
            'port' => JsonValueGuard::integer(Arr::get($data, 'port')),
            'username' => JsonValueGuard::string(Arr::get($data, 'username')),
            'max_databases' => null,
            'node_id' => JsonValueGuard::nullableInteger(Arr::get($data, 'node_id')),
        ]);

        // Confirm access using the provided credentials before saving data.
        $this->dynamic->set('dynamic', $host);
        DB::connection('dynamic')->select('SELECT 1 FROM dual');

        // The host row and its extension values are written together so a failing
        // extension cannot leave a host behind without them.
        return DB::transaction(function () use ($host, $extensions): DatabaseHost {
            $host->save();
            $this->extensions->save($host, $extensions);

            return $host;
        });
    }
}
