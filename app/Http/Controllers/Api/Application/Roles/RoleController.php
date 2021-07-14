<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Roles;

use Illuminate\Http\Response;
use Pterodactyl\Models\Location;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\AdminRole;
use Spatie\QueryBuilder\QueryBuilder;
use Pterodactyl\Transformers\Api\Application\LocationTransformer;
use Pterodactyl\Transformers\Api\Application\AdminRoleTransformer;
use Pterodactyl\Exceptions\Http\QueryValueOutOfRangeHttpException;
use Pterodactyl\Http\Requests\Api\Application\Roles\GetRoleRequest;
use Pterodactyl\Http\Requests\Api\Application\Roles\GetRolesRequest;
use Pterodactyl\Http\Requests\Api\Application\Roles\StoreRoleRequest;
use Pterodactyl\Http\Requests\Api\Application\Roles\DeleteRoleRequest;
use Pterodactyl\Http\Requests\Api\Application\Roles\UpdateRoleRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class RoleController extends ApplicationApiController
{
    /**
     * RoleController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns an array of all roles.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(GetRolesRequest $request): array
    {
        $perPage = $request->query('per_page', 10);
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $roles = QueryBuilder::for(AdminRole::query())
            ->allowedFilters(['id', 'name'])
            ->allowedSorts(['id', 'name'])
            ->paginate($perPage);

        return $this->fractal->collection($roles)
            ->transformWith($this->getTransformer(AdminRoleTransformer::class))
            ->toArray();
    }

    /**
     * Returns a single role.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function view(GetRoleRequest $request, AdminRole $role): array
    {
        return $this->fractal->item($role)
            ->transformWith($this->getTransformer(AdminRoleTransformer::class))
            ->toArray();
    }

    /**
     * Creates a new role.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'sort_id' => 99,
        ]);
        $role = AdminRole::query()->create($data);

        return $this->fractal->item($role)
            ->transformWith($this->getTransformer(AdminRoleTransformer::class))
            ->respond(JsonResponse::HTTP_CREATED);
    }

    /**
     * Updates a role.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function update(UpdateRoleRequest $request, AdminRole $role): array
    {
        $role->update($request->validated());

        return $this->fractal->item($role)
            ->transformWith($this->getTransformer(AdminRoleTransformer::class))
            ->toArray();
    }

    /**
     * Deletes a role.
     *
     * @throws \Exception
     */
    public function delete(DeleteRoleRequest $request, AdminRole $role): Response
    {
        $role->delete();

        return $this->returnNoContent();
    }
}
