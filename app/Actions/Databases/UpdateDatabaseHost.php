<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Databases;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Databases\UpdatesDatabaseHosts;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;

final readonly class UpdateDatabaseHost implements UpdatesDatabaseHosts
{
    public function __construct(private DynamicDatabaseConnection $dynamic, private ExtensionFields $extensions) {}

    public function update(DatabaseHost $host, array $input): DatabaseHost
    {
        $validated = Validator::validate($input, CreateDatabaseHost::rules());
        // Blank strings are null, as they are in a request.
        $validated = array_map(fn (mixed $value): mixed => is_string($value) && blank($value) ? null : $value, $validated);

        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        // A host given no node becomes global, and a blank password keeps the stored one.
        $attributes = [...Arr::except($validated, 'password'), 'node_id' => $validated['node_id'] ?? null];
        if (isset($validated['password'])) {
            $attributes['password'] = Crypt::encrypt($validated['password']);
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
