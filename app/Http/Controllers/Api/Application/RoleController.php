<?php

namespace Pterodactyl\Http\Controllers\Api\Application;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Requests\Admin\RoleFormRequest;
use Pterodactyl\Repositories\Eloquent\AdminRolesRepository;

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
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return new JsonResponse($this->repository->all());
    }

    /**
     * Creates a new role.
     *
     * @param \Pterodactyl\Http\Requests\Admin\RoleFormRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function create(RoleFormRequest $request)
    {
        $role = $this->repository->create($request->normalize());

        return new JsonResponse($role);
    }

    /**
     * Updates a role.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update()
    {
        return response('', 204);
    }

    /**
     * Deletes a role.
     *
     * @param int $role_id
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete(int $role_id)
    {
        $this->repository->delete($role_id);

        return response('', 204);
    }
}
