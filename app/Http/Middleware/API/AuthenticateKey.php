<?php

namespace Pterodactyl\Http\Middleware\API;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateKey
{
    /**
     * @var \Illuminate\Auth\AuthManager
     */
    private $auth;

    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface
     */
    private $repository;

    /**
     * AuthenticateKey constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface $repository
     * @param \Illuminate\Auth\AuthManager                                $auth
     */
    public function __construct(
        ApiKeyRepositoryInterface $repository,
        AuthManager $auth
    ) {
        $this->auth = $auth;
        $this->repository = $repository;
    }

    /**
     * Handle an API request by verifying that the provided API key
     * is in a valid format, and the route being accessed is allowed
     * for the given key.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function handle(Request $request, Closure $next)
    {
        if (is_null($request->bearerToken())) {
            throw new HttpException(401, null, null, ['WWW-Authenticate' => 'Bearer']);
        }

        try {
            $model = $this->repository->findFirstWhere([['token', '=', $request->bearerToken()]]);
        } catch (RecordNotFoundException $exception) {
            throw new AccessDeniedHttpException;
        }

        $this->auth->guard()->loginUsingId($model->user_id);
        $request->attributes->set('api_key', $model);

        return $next($request);
    }
}
