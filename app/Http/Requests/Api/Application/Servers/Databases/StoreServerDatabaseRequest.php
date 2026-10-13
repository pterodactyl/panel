<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Servers\Databases;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Support\DatabaseName;

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
     * The validated input under the action's field names: the database name gets its
     * "s{server_id}_" prefix, and host is the action's database_host_id.
     *
     * @return array<string, mixed>
     */
    public function actionInput(): array
    {
        $input = parent::actionInput();

        return [
            'database' => $this->databaseName(),
            'remote' => $input['remote'] ?? null,
            'database_host_id' => $input['host'] ?? null,
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

        return DatabaseName::generateUnique($this->string('database')->toString(), $server->id);
    }
}
