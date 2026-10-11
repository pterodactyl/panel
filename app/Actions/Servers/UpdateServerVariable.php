<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Pterodactyl\Contracts\Servers\UpdatesServerVariable;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerVariable;

final readonly class UpdateServerVariable implements UpdatesServerVariable
{
    public function update(Server $server, int $variableId, string $value): void
    {
        ServerVariable::query()->updateOrCreate(
            ['server_id' => $server->id, 'variable_id' => $variableId],
            ['variable_value' => $value],
        );
    }
}
