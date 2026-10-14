<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Users;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Users\GetUsersRequest;
use Pterodactyl\Http\Resources\Api\Admin\UserResource;
use Pterodactyl\Models\User;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Users', 'Create, update, retrieve, and delete panel users.')]
class ExternalController extends AdminApiController
{
    /**
     * Show user by external identifier.
     */
    #[Endpoint('Get user by external ID', 'Returns a single panel user by external identifier.')]
    #[ResponseFromResource(UserResource::class, User::class, description: 'User returned.', resourceKey: 'user')]
    public function __invoke(GetUsersRequest $request, string $external_id): UserResource
    {
        $user = User::query()->where('external_id', $external_id)->firstOrFail();

        return UserResource::make($user);
    }
}
