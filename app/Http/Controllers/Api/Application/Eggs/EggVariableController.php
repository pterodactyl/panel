<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Eggs;

use Pterodactyl\Models\Egg;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Eggs\Variables\VariableUpdateService;
use Pterodactyl\Services\Eggs\Variables\VariableCreationService;
use Pterodactyl\Transformers\Api\Application\EggVariableTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Eggs\Variables\StoreEggVariableRequest;
use Pterodactyl\Http\Requests\Api\Application\Eggs\Variables\UpdateEggVariablesRequest;

class EggVariableController extends ApplicationApiController
{
    private ConnectionInterface $connection;

    private VariableCreationService $variableCreationService;

    private VariableUpdateService $variableUpdateService;

    public function __construct(ConnectionInterface $connection, VariableCreationService $variableCreationService, VariableUpdateService $variableUpdateService)
    {
        parent::__construct();

        $this->connection = $connection;
        $this->variableCreationService = $variableCreationService;
        $this->variableUpdateService = $variableUpdateService;
    }

    /**
     * Creates a new egg variable.
     *
     * @throws \Pterodactyl\Exceptions\Service\Egg\Variable\BadValidationRuleException
     * @throws \Pterodactyl\Exceptions\Service\Egg\Variable\ReservedVariableNameException
     */
    public function store(StoreEggVariableRequest $request, Egg $egg): array
    {
        $variable = $this->variableCreationService->handle($egg->id, $request->validated());

        return $this->fractal->item($variable)
            ->transformWith(EggVariableTransformer::class)
            ->toArray();
    }

    /**
     * Updates multiple egg variables.
     *
     * @throws \Throwable
     */
    public function update(UpdateEggVariablesRequest $request, Egg $egg): array
    {
        $validated = $request->validated();

        $this->connection->transaction(function () use($egg, $validated) {
            foreach ($validated as $data) {
                $this->variableUpdateService->handle($egg, $data);
            }
        });

        return $this->fractal->collection($egg->refresh()->variables)
            ->transformWith(EggVariableTransformer::class)
            ->toArray();
    }
}
