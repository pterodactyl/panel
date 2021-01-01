<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Roles;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\AdminRole;
use Pterodactyl\Repositories\Eloquent\AdminRolesRepository;
use Pterodactyl\Transformers\Api\Application\AdminRoleTransformer;
use Pterodactyl\Http\Requests\Api\Application\Roles\GetRolesRequest;
use Pterodactyl\Http\Requests\Api\Application\Roles\StoreRoleRequest;
use Pterodactyl\Http\Requests\Api\Application\Roles\DeleteRoleRequest;
use Pterodactyl\Http\Requests\Api\Application\Roles\UpdateRoleRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class RoleController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\AdminRolesRepository
     */
    private $repository;

    /**
     * RolesController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\AdminRolesRepository $repository
     */
    public function __construct(AdminRolesRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Returns an array of all roles.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Roles\GetRolesRequest $request
     *
     * @return array
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Roles\GetRolesRequest $request
     * @param \Pterodactyl\Models\AdminRole $role
     *
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function view(GetRolesRequest $request, AdminRole $role): array
    {
        return $this->fractal->item($role)
            ->transformWith($this->getTransformer(AdminRoleTransformer::class))
            ->toArray();
    }

    /**
     * Creates a new role.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Roles\StoreRoleRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = AdminRole::query()->create($request->validated());

        return $this->fractal->item($role)
            ->transformWith($this->getTransformer(AdminRoleTransformer::class))
            ->respond(JsonResponse::HTTP_CREATED);
    }

    /**
     * Updates a role.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Roles\UpdateRoleRequest $request
     * @param \Pterodactyl\Models\AdminRole $role
     *
     * @return array
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Roles\DeleteRoleRequest $request
     * @param \Pterodactyl\Models\AdminRole $role
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DeleteRoleRequest $request, AdminRole $role): JsonResponse
    {
        $this->repository->delete($role->id);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
