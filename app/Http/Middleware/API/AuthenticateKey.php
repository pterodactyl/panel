<?php

namespace Pterodactyl\Http\Middleware\API;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class AuthenticateKey
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface
     */
    private $repository;

    /**
     * AuthenticateKey constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface $repository
     */
    public function __construct(ApiKeyRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Handle an API request by verifying that the provided API key
     * is in a valid format, and the route being accessed is allowed
     * for the given key.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     */
    public function handle(Request $request, Closure $next)
    {
        $this->repository->findFirstWhere([
            '',
        ]);
    }
}
