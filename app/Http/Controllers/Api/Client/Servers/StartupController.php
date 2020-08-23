<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\CarbonImmutable;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Pterodactyl\Repositories\Eloquent\ServerVariableRepository;
use Pterodactyl\Transformers\Api\Client\EggVariableTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Pterodactyl\Http\Requests\Api\Client\Servers\Startup\UpdateStartupVariableRequest;

class StartupController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Services\Servers\VariableValidatorService
     */
    private $service;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerVariableRepository
     */
    private $repository;

    /**
     * StartupController constructor.
     *
     * @param \Pterodactyl\Services\Servers\VariableValidatorService $service
     * @param \Pterodactyl\Repositories\Eloquent\ServerVariableRepository $repository
     */
    public function __construct(VariableValidatorService $service, ServerVariableRepository $repository)
    {
        parent::__construct();

        $this->service = $service;
        $this->repository = $repository;
    }

    /**
     * Updates a single variable for a server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Startup\UpdateStartupVariableRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return array
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateStartupVariableRequest $request, Server $server)
    {
        /** @var \Pterodactyl\Models\EggVariable $variable */
        $variable = $server->variables()->where('env_variable', $request->input('key'))->first();

        if (is_null($variable) || !$variable->user_viewable) {
            throw new BadRequestHttpException(
                "The environment variable you are trying to edit does not exist."
            );
        } else if (! $variable->user_editable) {
            throw new BadRequestHttpException(
                "The environment variable you are trying to edit is read-only."
            );
        }

        // Revalidate the variable value using the egg variable specific validation rules for it.
        $this->validate($request, ['value' => $variable->rules]);

        $this->repository->updateOrCreate([
            'server_id' => $server->id,
            'variable_id' => $variable->id,
        ], [
            'variable_value' => $request->input('value'),
        ]);

        $variable = $variable->refresh();
        $variable->server_value = $request->input('value');

        return $this->fractal->item($variable)
            ->transformWith($this->getTransformer(EggVariableTransformer::class))
            ->toArray();
    }
}
