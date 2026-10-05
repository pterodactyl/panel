<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use LogicException;
use Pterodactyl\Contracts\Servers\ChangesServerEgg;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerVariable;
use Throwable;

final readonly class ChangeServerEgg implements ChangesServerEgg
{
    /**
     * @throws Throwable
     */
    public function change(Server $server, Egg $egg, bool $keepVariables = false): Server
    {
        return DB::transaction(function () use ($server, $egg, $keepVariables): Server {
            $carried = $keepVariables ? $this->storedValues($server) : [];

            // Stored values point at the previous egg's variables, which the
            // server can no longer see once it has moved.
            ServerVariable::query()->where('server_id', $server->id)->delete();

            foreach ($egg->variables()->get() as $variable) {
                $value = $carried[$variable->env_variable] ?? null;
                if ($value === null || Validator::make(['value' => $value], ['value' => $variable->rules])->fails()) {
                    continue;
                }

                ServerVariable::query()->create([
                    'server_id' => $server->id,
                    'variable_id' => $variable->id,
                    'variable_value' => $value,
                ]);
            }

            $server->forceFill([
                'egg_id' => $egg->id,
                'startup' => $egg->startup ?? $server->startup,
                'image' => Arr::first($egg->docker_images) ?? $server->image,
            ])->save();

            $fresh = $server->fresh();
            throw_if($fresh === null, LogicException::class, 'The modified server no longer exists.');

            return $fresh;
        });
    }

    /**
     * @return array<string, string> environment variable name => stored value
     */
    private function storedValues(Server $server): array
    {
        return ServerVariable::query()
            ->where('server_id', $server->id)
            ->with('variable')
            ->get()
            ->mapWithKeys(fn (ServerVariable $stored): array => [$stored->variable->env_variable => $stored->variable_value])
            ->all();
    }
}
