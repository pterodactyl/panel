<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Eggs;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Eggs\ReordersEggVariables;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\Variables\ReorderVariablesRequest;
use Pterodactyl\Http\Resources\Api\Admin\EggVariableResource;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Egg Variables', 'Create and manage startup variables for an egg.')]
class ReorderVariablesController extends AdminApiController
{
    /**
     * Reorder egg variables.
     */
    #[Endpoint('Reorder egg variables', 'Updates the display order for variables on an egg.')]
    #[ResponseFromResource(EggVariableResource::class, EggVariable::class, description: 'Egg variables reordered.', collection: true, resourceKey: 'egg_variable')]
    public function __invoke(ReorderVariablesRequest $request, ReordersEggVariables $reorder, Egg $egg): ApiResourceCollection
    {
        $reorder->reorder($egg, $request->order());

        Activity::event('admin:egg-variable.reorder')
            ->subject($egg)
            ->log();

        $variables = $egg->variables()
            ->orderByRaw('sort_order IS NULL, sort_order ASC')
            ->orderBy('id')
            ->get();

        return EggVariableResource::collection($variables);
    }
}
