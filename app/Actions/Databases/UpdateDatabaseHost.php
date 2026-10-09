<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Databases;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Databases\UpdatesDatabaseHosts;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;
use Throwable;

final readonly class UpdateDatabaseHost implements UpdatesDatabaseHosts
{
    public function __construct(private DynamicDatabaseConnection $dynamic, private ExtensionFields $extensions) {}

    /**
     * Update a database host and persist to the database.
     *
     * @param  DatabaseHostUpdateData  $data
     *
     * @throws Throwable
     */
    public function update(DatabaseHost $host, array $data): DatabaseHost
    {
        $extensions = ValidatedExtensionValues::of($data['extensions'] ?? null);
        unset($data['extensions']);

        $password = $data['password'] ?? null;
        if ($password !== null && $password !== '') {
            $data['password'] = Crypt::encrypt($password);
        } else {
            unset($data['password']);
        }

        // Confirm access using the new credentials before saving them.
        $host->fill($data);
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
