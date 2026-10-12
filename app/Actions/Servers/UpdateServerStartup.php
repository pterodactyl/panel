<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use LogicException;
use Pterodactyl\Contracts\Servers\ChangesServerEgg;
use Pterodactyl\Contracts\Servers\UpdatesServerDockerImage;
use Pterodactyl\Contracts\Servers\UpdatesServerStartup;
use Pterodactyl\Contracts\Servers\UpdatesServerVariable;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Servers\VariableValidatorService;
use UnexpectedValueException;

final readonly class UpdateServerStartup implements UpdatesServerStartup
{
    public function __construct(
        private VariableValidatorService $validatorService,
        private ChangesServerEgg $eggs,
        private UpdatesServerDockerImage $dockerImage,
        private UpdatesServerVariable $variables,
    ) {}

    /**
     * The environment maps each egg variable to its value, which the egg's own variable rules
     * check. Below the admin level the environment is the only field.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(int $userLevel = User::USER_LEVEL_USER): array
    {
        if ($userLevel !== User::USER_LEVEL_ADMIN) {
            return ['environment' => ['present', 'array']];
        }

        $rules = CreateServer::rules();

        return [
            'startup' => $rules['startup'],
            'environment' => ['present', 'array'],
            'egg_id' => ['required', 'exists:eggs,id'],
            'docker_image' => $rules['image'],
            'skip_scripts' => ['present', 'boolean'],
        ];
    }

    public function update(Server $server, array $input, int $userLevel = User::USER_LEVEL_USER): Server
    {
        // Only the fields given are validated, and fields above the user level are ignored.
        $rules = array_map(fn (array $rules): array => ['sometimes', ...$rules], self::rules($userLevel));
        $validated = Validator::make($input, $rules)->safe();

        // Laravel's typed accessors would turn every value into one type, but the egg's
        // variable rules have to see each value as it was sent.
        $environment = [];
        foreach ($validated->array('environment') as $key => $value) {
            throw_unless(is_string($key) && ($value === null || is_scalar($value)), UnexpectedValueException::class, 'Server environment variables must map names to scalar values.');
            $environment[$key] = $value;
        }

        return DB::transaction(function () use ($server, $validated, $environment, $userLevel): Server {
            $eggId = $validated->has('egg_id') ? $validated->integer('egg_id') : null;
            if ($userLevel === User::USER_LEVEL_ADMIN && $eggId !== null && $server->egg_id !== $eggId) {
                // Only administrators may move a server to another egg. Values the
                // new egg still accepts are kept, and anything submitted below
                // replaces the defaults the move resets.
                $server = $this->eggs->change($server, Egg::query()->findOrFail($eggId), keepVariables: true);
            }

            if ($environment !== []) {
                $egg = $server->relationLoaded('egg') && $server->egg instanceof Egg
                    ? $server->egg
                    : Egg::query()->findOrFail($server->egg_id);

                $results = $this->validatorService->validate($egg, $environment, $userLevel);
                foreach ($results as $result) {
                    $this->variables->update($server, $result->id, $result->value === null ? '' : (string) $result->value);
                }
            }

            if ($userLevel === User::USER_LEVEL_ADMIN) {
                $server->fill([
                    'startup' => $validated['startup'] ?? $server->startup,
                    'skip_scripts' => $validated['skip_scripts'] ?? false,
                ])->save();

                // Image changes go through their own contract so extensions wrapping
                // it see this path too. Skip it when the image is unchanged.
                $image = $validated->has('docker_image') ? $validated->string('docker_image')->toString() : null;
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
