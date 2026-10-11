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
     * @param  array<string, mixed>  $input
     *
     * @throws Throwable
     */
    public function update(DatabaseHost $host, array $input): DatabaseHost
    {
        $attributes = CreateDatabaseHost::attributes($input);
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        if (isset($attributes['password'])) {
            $attributes['password'] = Crypt::encrypt($attributes['password']);
        }

        // Confirm access using the new credentials before saving them.
        $host->fill($attributes);
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
