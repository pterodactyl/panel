<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Databases;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Databases\UpdatesDatabaseHosts;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Models\DatabaseHost;
use Throwable;

final readonly class UpdateDatabaseHost implements UpdatesDatabaseHosts
{
    public function __construct(private DynamicDatabaseConnection $dynamic) {}

    /**
     * Update a database host and persist to the database.
     *
     * @param  DatabaseHostUpdateData  $data
     *
     * @throws Throwable
     */
    public function update(DatabaseHost $host, array $data): DatabaseHost
    {
        $password = $data['password'] ?? null;
        if ($password !== null && $password !== '') {
            $data['password'] = Crypt::encrypt($password);
        } else {
            unset($data['password']);
        }

        return DB::transaction(function () use ($data, $host): DatabaseHost {
            $host->update($data);
            $this->dynamic->set('dynamic', $host);
            DB::connection('dynamic')->select('SELECT 1 FROM dual');

            return $host;
        });
    }
}
