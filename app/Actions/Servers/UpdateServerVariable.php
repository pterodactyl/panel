<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Pterodactyl\Contracts\Servers\UpdatesServerVariable;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerVariable;

final readonly class UpdateServerVariable implements UpdatesServerVariable
{
    public function update(Server $server, int $variableId, string $value): void
    {
        $original = ServerVariable::query()->where('server_id', $server->id)->where('variable_id', $variableId)->first()?->variable_value;

        ServerVariable::query()->updateOrCreate(
            ['server_id' => $server->id, 'variable_id' => $variableId],
            ['variable_value' => $value],
        );

        if ($original !== $value) {
            $variable = EggVariable::query()->findOrFail($variableId);

            Activity::event('server:startup.edit')
                ->subject($server, $variable)
                ->property(['variable' => $variable->env_variable, 'old' => $original, 'new' => $value])
                ->log();
        }
    }
}
