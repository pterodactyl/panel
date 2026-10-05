<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Eggs;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Eggs\CreatesEggVariables;
use Pterodactyl\Contracts\Eggs\UpdatesEggVariables;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\Variables\DeleteVariableRequest;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\Variables\GetVariablesRequest;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\Variables\StoreVariableRequest;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\Variables\UpdateVariableRequest;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Transformers\Api\Admin\EggVariableTransformer;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Egg Variables', 'Create and manage startup variables for an egg.')]
class EggVariableController extends AdminApiController
{
    /**
     * List egg variables.
     *
     * @return ApiPayload
     */
    #[Endpoint('List egg variables', 'Returns variables for an egg in display order.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Unknown includes are ignored.', required: false, example: 'egg')]
    #[ResponseFromTransformer(EggVariableTransformer::class, EggVariable::class, description: 'Egg variables returned.', collection: true, resourceKey: 'egg_variable')]
    public function index(GetVariablesRequest $request, Egg $egg): array
    {

        return Fractal::collection($this->orderedVariables($egg))
            ->transformWith($this->getTransformer(EggVariableTransformer::class))
            ->toResponseArray();
    }

    /**
     * Create egg variable.
     */
    #[Endpoint('Create egg variable', 'Creates a startup variable for an egg.')]
    #[ResponseFromTransformer(EggVariableTransformer::class, EggVariable::class, status: 201, description: 'Egg variable created.', resourceKey: 'egg_variable')]
    public function store(StoreVariableRequest $request, CreatesEggVariables $variables, Egg $egg): JsonResponse
    {

        $variable = $variables->create($egg, $request->payload());

        Activity::event('admin:egg-variable.create')
            ->subject($egg, $variable)
            ->property('name', $variable->name)
            ->log();

        return Fractal::item($variable)
            ->transformWith($this->getTransformer(EggVariableTransformer::class))
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Update egg variable.
     *
     * @return ApiPayload
     */
    #[Endpoint('Update egg variable', 'Updates a startup variable for an egg.')]
    #[ResponseFromTransformer(EggVariableTransformer::class, EggVariable::class, description: 'Egg variable updated.', resourceKey: 'egg_variable')]
    public function update(UpdateVariableRequest $request, UpdatesEggVariables $variables, Egg $egg, EggVariable $variable): array
    {
        $variables->update($variable, $request->payload());

        $variable = $variable->refresh();

        Activity::event('admin:egg-variable.update')
            ->subject($egg, $variable)
            ->property('name', $variable->name)
            ->log();

        return Fractal::item($variable)
            ->transformWith($this->getTransformer(EggVariableTransformer::class))
            ->toResponseArray();
    }

    /**
     * Delete egg variable.
     */
    #[Endpoint('Delete egg variable', 'Deletes a startup variable from an egg.')]
    #[ScribeResponse(status: 204, description: 'Egg variable deleted.')]
    public function destroy(DeleteVariableRequest $request, Egg $egg, EggVariable $variable): Response
    {
        $variable->delete();

        Activity::event('admin:egg-variable.delete')
            ->subject($egg, $variable)
            ->property('name', $variable->name)
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Return egg variables in display order.
     *
     * @return Collection<int, EggVariable>
     */
    private function orderedVariables(Egg $egg): Collection
    {
        return $egg->variables()
            ->orderByRaw('sort_order IS NULL, sort_order ASC')
            ->orderBy('id')
            ->get();
    }
}
