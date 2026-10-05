<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Users;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Users\GetUsersRequest;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Users\UserFinderService;
use Pterodactyl\Transformers\Api\Admin\UserTransformer;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Users', 'Create, update, retrieve, and delete panel users.')]
class ExternalController extends AdminApiController
{
    /**
     * Show user by external identifier.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get user by external ID', 'Returns a single panel user by external identifier.')]
    #[ResponseFromTransformer(UserTransformer::class, User::class, description: 'User returned.', resourceKey: 'user')]
    public function __invoke(GetUsersRequest $request, UserFinderService $finder, string $external_id): array
    {
        $user = $finder->byExternalId($external_id);

        return Fractal::item($user)
            ->transformWith($this->getTransformer(UserTransformer::class))
            ->toResponseArray();
    }
}
