<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Users;

use Pterodactyl\Transformers\Api\Application\UserTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Users\GetExternalUserRequest;

class ExternalUserController extends ApplicationApiController
{
    /**
     * Retrieve a specific user from the database using their external ID.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Users\GetExternalUserRequest $request
     *
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(GetExternalUserRequest $request): array
    {
        return $this->fractal->item($request->getUserModel())
            ->transformWith($this->getTransformer(UserTransformer::class))
            ->toArray();
    }
}
