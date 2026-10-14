<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application\Users;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Users\GetExternalUserRequest;
use Pterodactyl\Http\Resources\Api\Application\UserResource;
use Pterodactyl\Models\User;

#[Group('Application API', 'Root administrator endpoints for managing panel resources using application API tokens.')]
#[Subgroup('Users', 'Create, update, retrieve, and delete panel users.')]
class ExternalUserController extends ApplicationApiController
{
    /**
     * Retrieve a specific user from the database using their external ID.
     */
    #[Endpoint('Get user by external ID', 'Returns a single panel user by its external identifier.')]
    #[ResponseFromResource(UserResource::class, User::class, resourceKey: 'user')]
    public function index(GetExternalUserRequest $request, string $external_id): UserResource
    {
        $user = User::query()->where('external_id', $external_id)->firstOrFail();

        return UserResource::make($user);
    }
}
