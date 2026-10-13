<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Databases;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Pterodactyl\Actions\Databases\DeployServerDatabase;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\DatabaseName;

class StoreDatabaseRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permissions::DatabaseCreate->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        $server = $this->parameter('server', Server::class);
        $rules = DeployServerDatabase::rules();

        return [
            'database' => [
                ...$rules['database'],
                // The name only needs to be unique to this server, not across database hosts.
                Rule::unique('databases')->where(function (Builder $query) use ($server): void {
                    $query->where('server_id', $server->id)
                        ->where('database', DatabaseName::generateUnique($this->string('database')->toString(), $server->id));
                }),
            ],
            'remote' => $rules['remote'],
        ];
    }

    public function messages(): array
    {
        return [
            'database.unique' => 'The database name you have selected is already in use by this server.',
        ];
    }
}
