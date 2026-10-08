<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Startup\GetStartupRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Startup\UpdateStartupVariableRequest;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\StartupCommandService;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Transformers\Api\Client\EggVariableTransformer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UnexpectedValueException;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Startup', 'View startup command data and update editable startup variables for an accessible server.')]
class StartupController extends ClientApiController
{
    private const array BAD_REQUEST_ERROR = [
        'errors' => [
            [
                'code' => 'BadRequestHttpException',
                'status' => '400',
                'detail' => 'The environment variable you are trying to edit is read-only.',
            ],
        ],
    ];

    /**
     * Returns the startup information for the server including all the variables.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get startup configuration', 'Returns the rendered startup command, available Docker images, raw startup command, and user-visible variables for the server.')]
    #[ResponseFromTransformer(EggVariableTransformer::class, EggVariable::class, description: 'Startup configuration returned.', collection: true, factoryStates: ['viewable', 'editable'], resourceKey: 'egg_variable', meta: ['startup_command' => 'java -Xms128M -Xmx1024M -jar paper.jar', 'docker_images' => ['Java 23' => 'ghcr.io/pterodactyl/yolks:java_23'], 'raw_startup_command' => 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}}'])]
    public function index(GetStartupRequest $request, StartupCommandService $startupCommand, Server $server): array
    {
        $startup = $startupCommand->handle($server);
        $egg = $server->egg ?? throw new UnexpectedValueException('The server does not have an egg relationship.');

        return Fractal::collection(
            $server->variables()->where('user_viewable', true)->get()
        )
            ->transformWith($this->getTransformer(EggVariableTransformer::class))
            ->addMeta([
                'startup_command' => $startup,
                'docker_images' => $egg->docker_images,
                'raw_startup_command' => $server->startup,
            ])
            ->toResponseArray();
    }

    /**
     * Updates a single variable for a server.
     *
     * @return ApiPayload
     */
    #[Endpoint('Update startup variable', 'Updates one editable startup variable for the server and returns the updated rendered startup command.')]
    #[ResponseFromTransformer(EggVariableTransformer::class, EggVariable::class, description: 'Startup variable updated.', factoryStates: ['viewable', 'editable'], resourceKey: 'egg_variable', meta: ['startup_command' => 'java -Xms128M -Xmx1024M -jar paper.jar', 'raw_startup_command' => 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}}'])]
    #[ScribeResponse(self::BAD_REQUEST_ERROR, status: 400, description: 'The variable does not exist, is hidden, or is read-only.')]
    public function update(UpdateStartupVariableRequest $request, StartupCommandService $startupCommand, Server $server): array
    {
        $variable = $request->variable();
        throw_if(! $variable instanceof EggVariable || ! $variable->user_viewable, BadRequestHttpException::class, 'The environment variable you are trying to edit does not exist.');

        throw_unless($variable->user_editable, BadRequestHttpException::class, 'The environment variable you are trying to edit is read-only.');

        $original = $variable->server_value;
        $value = JsonValueGuard::nullableScalarString($request->validated('value'));

        $server->serverVariables()->updateOrCreate(
            ['variable_id' => $variable->id],
            ['variable_value' => $value ?? ''],
        );

        $variable->server_value = $value;

        $startup = $startupCommand->handle($server);

        if ($original !== $value) {
            Activity::event('server:startup.edit')
                ->subject($variable)
                ->property([
                    'variable' => $variable->env_variable,
                    'old' => $original,
                    'new' => $value,
                ])
                ->log();
        }

        return Fractal::item($variable)
            ->transformWith($this->getTransformer(EggVariableTransformer::class))
            ->addMeta([
                'startup_command' => $startup,
                'raw_startup_command' => $server->startup,
            ])
            ->toResponseArray();
    }
}
