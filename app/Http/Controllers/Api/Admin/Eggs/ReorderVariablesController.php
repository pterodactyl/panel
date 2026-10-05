<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Eggs;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Eggs\ReordersEggVariables;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\Variables\ReorderVariablesRequest;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Transformers\Api\Admin\EggVariableTransformer;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Egg Variables', 'Create and manage startup variables for an egg.')]
class ReorderVariablesController extends AdminApiController
{
    /**
     * Reorder egg variables.
     *
     * @return ApiPayload
     */
    #[Endpoint('Reorder egg variables', 'Updates the display order for variables on an egg.')]
    #[ResponseFromTransformer(EggVariableTransformer::class, EggVariable::class, description: 'Egg variables reordered.', collection: true, resourceKey: 'egg_variable')]
    public function __invoke(ReorderVariablesRequest $request, ReordersEggVariables $reorder, Egg $egg): array
    {
        $reorder->reorder($egg, $request->order());

        Activity::event('admin:egg-variable.reorder')
            ->subject($egg)
            ->log();

        $variables = $egg->variables()
            ->orderByRaw('sort_order IS NULL, sort_order ASC')
            ->orderBy('id')
            ->get();

        return Fractal::collection($variables)
            ->transformWith($this->getTransformer(EggVariableTransformer::class))
            ->toResponseArray();
    }
}
