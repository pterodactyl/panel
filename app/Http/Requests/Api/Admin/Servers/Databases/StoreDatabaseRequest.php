<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers\Databases;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\DatabaseName;
use Pterodactyl\Support\JsonValueGuard;

class StoreDatabaseRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminServerDatabasesCreate];
    }

    /**
     * Validation rules for creating a server database.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $server = $this->parameter('server', Server::class);

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
            'database_host_id' => ['required', 'integer', 'exists:database_hosts,id'],
            'max_connections' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Format the validated data for the service to consume.
     *
     * @return DatabaseCreationData
     */
    public function payload(): array
    {
        return [
            'database' => $this->string('database')->toString(),
            'remote' => $this->string('remote')->toString(),
            'database_host_id' => $this->integer('database_host_id'),
            'max_connections' => $this->filled('max_connections') ? $this->integer('max_connections') : null,
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

    /** The unique, prefixed database name used by the uniqueness rule and the service. */
    public function databaseName(): string
    {
        $server = $this->parameter('server', Server::class);

        return DatabaseName::generateUnique(JsonValueGuard::string($this->input('database')), $server->id);
    }
}
