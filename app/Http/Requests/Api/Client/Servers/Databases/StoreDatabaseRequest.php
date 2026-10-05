<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Databases;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\DatabaseName;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Validation\DatabaseRules;

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

        return [
            'database' => [
                'required',
                'alpha_dash',
                'min:3',
                'max:48',
                // The name only needs to be unique to this server, not across database hosts.
                Rule::unique('databases')->where(function (Builder $query) use ($server): void {
                    $query->where('server_id', $server->id)
                        ->where('database', DatabaseName::generateUnique(JsonValueGuard::string($this->input('database')), $server->id));
                }),
            ],
            'remote' => DatabaseRules::rules()['remote'],
        ];
    }

    public function messages(): array
    {
        return [
            'database.unique' => 'The database name you have selected is already in use by this server.',
        ];
    }

    /** @return DatabaseDeploymentData */
    public function payload(): array
    {
        return [
            'database' => $this->string('database')->toString(),
            'remote' => $this->string('remote')->toString(),
        ];
    }
}
