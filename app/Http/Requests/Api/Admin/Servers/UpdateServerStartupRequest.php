<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers;

use Closure;
use Illuminate\Validation\Validator;
use Pterodactyl\Actions\Servers\UpdateServerStartup;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Concerns\ValidatesNodeAcceptsEgg;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;

class UpdateServerStartupRequest extends ServerWriteRequest
{
    use ValidatesNodeAcceptsEgg;

    public function permissions(): array
    {
        return [Permissions::AdminServersUpdate];
    }

    /**
     * Validation rules for updating a server's startup configuration.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [...UpdateServerStartup::rules(User::USER_LEVEL_ADMIN), 'ignore_tags' => ['sometimes', 'boolean']];
    }

    /**
     * Moving the server to another egg needs a node that accepts it.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $server = $this->parameter('server', Server::class);
            if ($validator->errors()->isNotEmpty() || $validator->safe()->integer('egg_id') === $server->egg_id) {
                return;
            }

            $this->validateNodeAcceptsEgg($validator, 'egg_id', $server->node, Egg::query()->findOrFail($validator->safe()->integer('egg_id')));
        }];
    }
}
