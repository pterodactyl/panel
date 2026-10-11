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
use Throwable;

final readonly class CreateDatabaseHost implements CreatesDatabaseHosts
{
    public function __construct(private DynamicDatabaseConnection $dynamic, private ExtensionFields $extensions) {}

    /**
     * A database host's fields, for creating one and for updating one. The host is a hostname
     * or IP address, and the node, when given, must exist.
     *
     * @return NormalizedValidationRules
     */
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

    /**
     * Validates database host input and returns the model attributes it describes. The
     * password comes back in plain text, for the action to encrypt, and is left out when it
     * is blank so an updated host keeps its stored one. A host given no node is global.
     *
     * @param  array<string, mixed>  $input
     * @return array{name: string, host: string, port: int, username: string, node_id: int|null, password?: string}
     */
    public static function attributes(array $input): array
    {
        $input = Validator::make($input, self::rules())->safe();

        $attributes = [
            'name' => $input->string('name')->toString(),
            'host' => $input->string('host')->toString(),
            'port' => $input->integer('port'),
            'username' => $input->string('username')->toString(),
            'node_id' => $input->filled('node_id') ? $input->integer('node_id') : null,
        ];

        if ($input->filled('password')) {
            $attributes['password'] = $input->string('password')->toString();
        }

        return $attributes;
    }

    /**
     * Create a new database host on the Panel once it accepts the given credentials.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws Throwable
     */
    public function create(array $input): DatabaseHost
    {
        $attributes = self::attributes($input);
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        $host = DatabaseHost::query()->make([
            ...$attributes,
            'password' => Crypt::encrypt($attributes['password'] ?? null),
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
