<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\StartupCommandService;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Pterodactyl\Repositories\Eloquent\ServerVariableRepository;
use Pterodactyl\Transformers\Api\Client\EggVariableTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Pterodactyl\Http\Requests\Api\Client\Servers\Startup\GetStartupRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Startup\UpdateStartupVariableRequest;

class StartupController extends ClientApiController
{
    private VariableValidatorService $service;
    private ServerVariableRepository $repository;
    private StartupCommandService $startupCommandService;

    /**
     * StartupController constructor.
     */
    public function __construct(VariableValidatorService $service, StartupCommandService $startupCommandService, ServerVariableRepository $repository)
    {
        parent::__construct();

        $this->service = $service;
        $this->repository = $repository;
        $this->startupCommandService = $startupCommandService;
    }

    /**
     * Returns the startup information for the server including all of the variables.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(GetStartupRequest $request, Server $server): array
    {
        $startup = $this->startupCommandService->handle($server, false);

        return $this->fractal->collection(
            $server->variables()->where('user_viewable', true)->get()
        )
            ->transformWith(EggVariableTransformer::class)
            ->addMeta([
                'startup_command' => $startup,
                'docker_images' => $server->egg->docker_images,
                'raw_startup_command' => $server->startup,
            ])
            ->toArray();
    }

    /**
     * Updates a single variable for a server.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function update(UpdateStartupVariableRequest $request, Server $server): array
    {
        $variable = $server->variables()->where('env_variable', $request->input('key'))->first();

        if (is_null($variable) || !$variable->user_viewable) {
            throw new BadRequestHttpException('The environment variable you are trying to edit does not exist.');
        } elseif (!$variable->user_editable) {
            throw new BadRequestHttpException('The environment variable you are trying to edit is read-only.');
        }

        // Revalidate the variable value using the egg variable specific validation rules for it.
        $this->validate($request, ['value' => $variable->rules]);

        $this->repository->updateOrCreate([
            'server_id' => $server->id,
            'variable_id' => $variable->id,
        ], [
            'variable_value' => $request->input('value') ?? '',
        ]);

        $variable = $variable->refresh();
        $variable->server_value = $request->input('value');

        $startup = $this->startupCommandService->handle($server, false);

        return $this->fractal->item($variable)
            ->transformWith(EggVariableTransformer::class)
            ->addMeta([
                'startup_command' => $startup,
                'raw_startup_command' => $server->startup,
            ])
            ->toArray();
    }
}
