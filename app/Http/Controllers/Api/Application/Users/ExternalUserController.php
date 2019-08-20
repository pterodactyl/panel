<?php

namespace App\Http\Controllers\Api\Application\Users;

use App\Transformers\Api\Application\UserTransformer;
use App\Http\Controllers\Api\Application\ApplicationApiController;
use App\Http\Requests\Api\Application\Users\GetExternalUserRequest;

class ExternalUserController extends ApplicationApiController
{
    /**
     * Retrieve a specific user from the database using their external ID.
     *
     * @param \App\Http\Requests\Api\Application\Users\GetExternalUserRequest $request
     * @return array
     */
    public function index(GetExternalUserRequest $request): array
    {
        return $this->fractal->item($request->getUserModel())
            ->transformWith($this->getTransformer(UserTransformer::class))
            ->toArray();
    }
}
