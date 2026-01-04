<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers\Databases;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Illuminate\Validation\Rule;
use Illuminate\Database\Query\Builder;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreServerDatabaseRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_SERVER_DATABASES;

    protected int $permission = AdminAcl::WRITE;

    /**
     * Validation rules for database creation.
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
                Rule::unique('databases')->where(function (Builder $query) use ($server) {
                    $query->where('server_id', $server->id)->where('database', $this->databaseName());
                }),
            ],
            'remote' => 'required|string|regex:/^[0-9%.]{1,15}$/',
            'host' => 'required|integer|exists:database_hosts,id',
        ];
    }

    /**
     * Return data formatted in the correct format for the service to consume.
     */
    public function validated($key = null, $default = null): array
    {
        return [
            'database' => $this->input('database'),
            'remote' => $this->input('remote'),
            'database_host_id' => $this->input('host'),
        ];
    }

    /**
     * Format error messages in a more understandable format for API output.
     */
    public function attributes(): array
    {
        return [
            'host' => 'Database Host Server ID',
            'remote' => 'Remote Connection String',
            'database' => 'Database Name',
        ];
    }

    /**
     * Returns the database name in the expected format.
     */
    public function databaseName(): string
    {
        $server = $this->route()->parameter('server');

        Assert::isInstanceOf($server, Server::class);

        return DatabaseManagementService::generateUniqueDatabaseName($this->input('database'), $server->id);
    }
}
