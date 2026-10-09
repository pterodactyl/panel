<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Support\Facades\DB;
use LogicException;
use Pterodactyl\Contracts\Servers\ChangesServerEgg;
use Pterodactyl\Contracts\Servers\UpdatesServerDockerImage;
use Pterodactyl\Contracts\Servers\UpdatesServerStartup;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerVariable;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Throwable;

final readonly class UpdateServerStartup implements UpdatesServerStartup
{
    public function __construct(
        private VariableValidatorService $validatorService,
        private ChangesServerEgg $eggs,
        private UpdatesServerDockerImage $dockerImage,
    ) {}

    /**
     * @param  ServerStartupModificationData  $data
     *
     * @throws Throwable
     */
    public function update(Server $server, array $data, int $userLevel = User::USER_LEVEL_USER): Server
    {
        return DB::transaction(function () use ($server, $data, $userLevel): Server {
            $eggId = $data['egg_id'] ?? null;
            if ($userLevel === User::USER_LEVEL_ADMIN && $eggId !== null && $server->egg_id !== $eggId) {
                // Only administrators may move a server to another egg. Values the
                // new egg still accepts are kept, and anything submitted below
                // replaces the defaults the move resets.
                $server = $this->eggs->change($server, Egg::query()->findOrFail($eggId), keepVariables: true);
            }

            if (! empty($data['environment'])) {
                $egg = $server->relationLoaded('egg') && $server->egg instanceof Egg
                    ? $server->egg
                    : Egg::query()->findOrFail($server->egg_id);

                $results = $this->validatorService->setUserLevel($userLevel)->handle($egg, $data['environment']);
                foreach ($results as $result) {
                    ServerVariable::query()->updateOrCreate(
                        ['server_id' => $server->id, 'variable_id' => $result->id],
                        ['variable_value' => $result->value ?? '']
                    );
                }
            }

            if ($userLevel === User::USER_LEVEL_ADMIN) {
                $server->fill([
                    'startup' => $data['startup'] ?? $server->startup,
                    'skip_scripts' => $data['skip_scripts'] ?? isset($data['skip_scripts']),
                ])->save();

                // Image changes go through their own contract so extensions wrapping
                // it see this path too. Skip it when the image is unchanged.
                $image = $data['docker_image'] ?? null;
                if ($image !== null && $image !== $server->image) {
                    $server = $this->dockerImage->update($server, $image);
                }
            }

            // Use fresh() rather than refresh(). refresh() reloads every loaded
            // relation through an eager load, which rebuilds Server::variables()
            // on a blank model. That relation captures $this->id in its join, so
            // the rebuilt join loses the server filter and the reloaded variables
            // come back wrong. fresh() returns a new instance and leaves the
            // relation unloaded.
            $fresh = $server->fresh();
            throw_if($fresh === null, LogicException::class, 'The modified server no longer exists.');

            return $fresh;
        });
    }
}
