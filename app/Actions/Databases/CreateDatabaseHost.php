<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Databases;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Databases\CreatesDatabaseHosts;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;

final readonly class CreateDatabaseHost implements CreatesDatabaseHosts
{
    public function __construct(private DynamicDatabaseConnection $dynamic, private ExtensionFields $extensions) {}

    /** @return NormalizedValidationRules */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'host' => ['required', 'string', 'regex:/^[\w\-\.]+$/'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'username' => ['required', 'string', 'max:32'],
            'password' => ['nullable', 'string'],
            'node_id' => ['sometimes', 'nullable', 'integer', 'exists:nodes,id'],
        ];
    }

    public function create(array $input): DatabaseHost
    {
        $validated = Validator::validate($input, self::rules());
        // Blank strings are null, as they are in a request.
        $validated = array_map(fn (mixed $value): mixed => is_string($value) && blank($value) ? null : $value, $validated);

        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        // A host given no node is global.
        $host = DatabaseHost::query()->make([
            ...$validated,
            'node_id' => $validated['node_id'] ?? null,
            'password' => Crypt::encrypt($validated['password'] ?? null),
            'max_databases' => null,
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
