<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Roles;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\AdminRole;
use Pterodactyl\Transformers\Api\Application\AdminRoleTransformer;
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
        return $this->fractal->collection(AdminRole::all())
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
    public function delete(DeleteRoleRequest $request, AdminRole $role): JsonResponse
    {
        $role->delete();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
