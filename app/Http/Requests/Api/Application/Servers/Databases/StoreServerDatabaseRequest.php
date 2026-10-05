<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Servers\Databases;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Support\DatabaseName;
use Pterodactyl\Support\JsonValueGuard;

class StoreServerDatabaseRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_SERVER_DATABASES;

    protected int $permission = AdminAcl::WRITE;

    /**
     * Validation rules for database creation.
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
            'host' => ['required', 'integer', 'exists:database_hosts,id'],
        ];
    }

    /**
     * Return data formatted in the correct format for the service to consume.
     *
     * @return DatabaseCreationData
     */
    public function payload(): array
    {
        return [
            'database' => $this->string('database')->toString(),
            'remote' => $this->string('remote')->toString(),
            'database_host_id' => $this->integer('host'),
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
        $server = $this->parameter('server', Server::class);

        return DatabaseName::generateUnique(JsonValueGuard::string($this->input('database')), $server->id);
    }
}
