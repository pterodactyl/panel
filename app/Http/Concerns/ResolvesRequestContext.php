<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Concerns;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use LogicException;
use Pterodactyl\Models\User;

trait ResolvesRequestContext
{
    protected function authenticatedUser(Request $request): User
    {
        return $request->user() ?? throw new AuthenticationException(__('exceptions.api.unauthenticated'));
    }

    /**
     * @return Route
     */
    protected function resolvedRoute(Request $request)
    {
        return $request->route() ?? throw new LogicException('Request middleware requires a resolved route.');
    }
}
