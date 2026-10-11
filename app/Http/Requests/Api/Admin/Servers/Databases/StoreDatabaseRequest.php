<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers\Databases;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Pterodactyl\Actions\Databases\CreateDatabase;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\DatabaseName;

class StoreDatabaseRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminServerDatabasesCreate];
    }

    /**
     * Validation rules for creating a server database. The name is the one before the
     * "s{server_id}_" prefix, and the remote is held to an IP address pattern.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $server = $this->parameter('server', Server::class);
        $rules = CreateDatabase::rules();

        return [
            'database' => [
                'required',
                'alpha_dash',
                'min:1',
                'max:48',
                Rule::unique('databases')->where(function (Builder $query) use ($server): void {
                    $query->where('server_id', $server->id)->where('database', $this->databaseName());
                }),
            ],
            'remote' => ['required', 'string', 'regex:/^[0-9%.]{1,15}$/'],
            'database_host_id' => $rules['database_host_id'],
            'max_connections' => $rules['max_connections'],
        ];
    }

    /**
     * The validated input, with the database name prefixed for the action. A database
     * created without a connection limit gets none.
     *
     * @return ActionInput
     */
    public function actionInput(): array
    {
        $input = parent::actionInput();

        return [
            ...$input,
            'database' => $this->databaseName(),
            'max_connections' => $input['max_connections'] ?? null,
        ];
    }

    /** Rename fields to be more clear in error messages. */
    public function attributes(): array
    {
        return [
            'database_host_id' => 'Database Host Server ID',
            'remote' => 'Remote Connection String',
            'database' => 'Database Name',
            'max_connections' => 'Max Connections',
        ];
    }

    /** The unique, prefixed database name used by the uniqueness rule and the action. */
    public function databaseName(): string
    {
        $server = $this->parameter('server', Server::class);

        return DatabaseName::generateUnique($this->string('database')->toString(), $server->id);
    }
}
