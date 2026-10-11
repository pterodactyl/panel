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
use Pterodactyl\Support\JsonValueGuard;
use Throwable;

/**
 * @phpstan-type StartupAttributes array{environment: array<string, ApiScalar>, egg_id?: int, startup?: string, docker_image?: string, skip_scripts?: bool}
 */
final readonly class UpdateServerStartup implements UpdatesServerStartup
{
    public function __construct(
        private VariableValidatorService $validatorService,
        private ChangesServerEgg $eggs,
        private UpdatesServerDockerImage $dockerImage,
        private UpdatesServerVariable $variables,
    ) {}

    /**
     * A server's startup fields at the given user level. The environment maps each egg
     * variable to its value, which the egg's own variable rules check. Only an administrator
     * changes the startup command, egg, Docker image and install script flag, so below the
     * admin level the environment is the only field.
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

    /**
     * Validates startup input at the given user level and returns the changes it describes.
     * Only the fields given are validated, and fields above the user level are ignored. The
     * environment keeps each value as it was given, for the egg's variable rules to check.
     *
     * @param  ActionInput  $input
     * @return StartupAttributes
     */
    public static function attributes(array $input, int $userLevel = User::USER_LEVEL_USER): array
    {
        $rules = [];
        foreach (self::rules($userLevel) as $field => $fieldRules) {
            $rules[$field] = ['sometimes', ...$fieldRules];
        }

        $input = Validator::make($input, $rules)->safe();

        // Laravel's typed accessors would turn every value into one type, but the egg's
        // variable rules have to see each value as it was sent.
        $environment = $input->array('environment');
        JsonValueGuard::assertScalarPayload($environment);

        $attributes = ['environment' => $environment];
        if ($userLevel !== User::USER_LEVEL_ADMIN) {
            return $attributes;
        }

        if ($input->has('egg_id')) {
            $attributes['egg_id'] = $input->integer('egg_id');
        }

        if ($input->has('startup')) {
            $attributes['startup'] = $input->string('startup')->toString();
        }

        if ($input->has('docker_image')) {
            $attributes['docker_image'] = $input->string('docker_image')->toString();
        }

        $attributes['skip_scripts'] = $input->boolean('skip_scripts');

        return $attributes;
    }

    /**
     * @param  ActionInput  $input
     *
     * @throws Throwable
     */
    public function update(Server $server, array $input, int $userLevel = User::USER_LEVEL_USER): Server
    {
        $attributes = self::attributes($input, $userLevel);

        return DB::transaction(function () use ($server, $attributes, $userLevel): Server {
            $eggId = $attributes['egg_id'] ?? null;
            if ($userLevel === User::USER_LEVEL_ADMIN && $eggId !== null && $server->egg_id !== $eggId) {
                // Only administrators may move a server to another egg. Values the
                // new egg still accepts are kept, and anything submitted below
                // replaces the defaults the move resets.
                $server = $this->eggs->change($server, Egg::query()->findOrFail($eggId), keepVariables: true);
            }

            if ($attributes['environment'] !== []) {
                $egg = $server->relationLoaded('egg') && $server->egg instanceof Egg
                    ? $server->egg
                    : Egg::query()->findOrFail($server->egg_id);

                $results = $this->validatorService->setUserLevel($userLevel)->handle($egg, $attributes['environment']);
                foreach ($results as $result) {
                    // SAFETY: validated variable values are JSON scalars, which all have a string form.
                    $this->variables->update($server, $result->id, $result->value === null ? '' : (string) $result->value);
                }
            }

            if ($userLevel === User::USER_LEVEL_ADMIN) {
                $server->fill([
                    'startup' => $attributes['startup'] ?? $server->startup,
                    'skip_scripts' => $attributes['skip_scripts'] ?? false,
                ])->save();

                // Image changes go through their own contract so extensions wrapping
                // it see this path too. Skip it when the image is unchanged.
                $image = $attributes['docker_image'] ?? null;
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
