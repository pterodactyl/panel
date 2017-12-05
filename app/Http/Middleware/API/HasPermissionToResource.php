<?php

namespace Pterodactyl\Http\Middleware\API;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class HasPermissionToResource
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface
     */
    private $repository;

    /**
     * HasPermissionToResource constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface $repository
     */
    public function __construct(ApiKeyRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Determine if an API key has permission to access the given route.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string                   $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role = 'admin')
    {
        /** @var \Pterodactyl\Models\APIKey $model */
        $model = $request->attributes->get('api_key');

        if ($role === 'admin' && ! $request->user()->root_admin) {
            throw new NotFoundHttpException;
        }

        $this->repository->loadPermissions($model);
        $routeKey = str_replace(['api.', 'admin.'], '', $request->route()->getName());

        $count = $model->getRelation('permissions')->filter(function ($permission) use ($routeKey) {
            return $routeKey === str_replace('-', '.', $permission->permission);
        })->count();

        if ($count === 1) {
            return $next($request);
        }

        throw new AccessDeniedHttpException('Cannot access resource without required `' . $routeKey . '` permission.');
    }
}
