<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Users;

use Illuminate\Support\Arr;
use Pterodactyl\Models\User;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Services\Users\UserDeletionService;
use Pterodactyl\Transformers\Api\Application\UserTransformer;
use Pterodactyl\Exceptions\Http\QueryValueOutOfRangeHttpException;
use Pterodactyl\Http\Requests\Api\Application\Users\GetUserRequest;
use Pterodactyl\Http\Requests\Api\Application\Users\GetUsersRequest;
use Pterodactyl\Http\Requests\Api\Application\Users\StoreUserRequest;
use Pterodactyl\Http\Requests\Api\Application\Users\DeleteUserRequest;
use Pterodactyl\Http\Requests\Api\Application\Users\UpdateUserRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class UserController extends ApplicationApiController
{
    /**
     * UserController constructor.
     */
    public function __construct(
        private UserCreationService $creationService,
        private UserDeletionService $deletionService,
        private UserUpdateService $updateService
    ) {
        parent::__construct();
    }

    /**
     * Handle request to list all users on the panel. Returns a JSON-API representation
     * of a collection of users including any defined relations passed in
     * the request.
     */
    public function index(GetUsersRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '10');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $users = QueryBuilder::for(User::query())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('uuid'),
                AllowedFilter::exact('external_id'),
                'username',
                'email',
                AllowedFilter::callback('*', function (Builder $builder, $value) {
                    foreach (Arr::wrap($value) as $datum) {
                        $datum = '%' . $datum . '%';
                        $builder->where(function (Builder $builder) use ($datum) {
                            $builder->where('uuid', 'LIKE', $datum)
                                ->orWhere('username', 'LIKE', $datum)
                                ->orWhere('email', 'LIKE', $datum)
                                ->orWhere('external_id', 'LIKE', $datum);
                        });
                    }
                }),
            ])
            ->allowedSorts(['id', 'uuid', 'username', 'email', 'admin_role_id'])
            ->paginate($perPage);

        return $this->fractal->collection($users)
            ->transformWith(UserTransformer::class)
            ->toArray();
    }

    /**
     * Handle a request to view a single user. Includes any relations that
     * were defined in the request.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function view(GetUserRequest $request, User $user): array
    {
        return $this->fractal->item($user)
            ->transformWith(UserTransformer::class)
            ->toArray();
    }

    /**
     * Update an existing user on the system and return the response. Returns the
     * updated user model response on success. Supports handling of token revocation
     * errors when switching a user from an admin to a normal user.
     *
     * Revocation errors are returned under the 'revocation_errors' key in the response
     * meta. If there are no errors this is an empty array.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function update(UpdateUserRequest $request, User $user): array
    {
        $this->updateService->setUserLevel(User::USER_LEVEL_ADMIN);
        $user = $this->updateService->handle($user, $request->validated());

        return $this->fractal->item($user)
            ->transformWith(UserTransformer::class)
            ->toArray();
    }

    /**
     * Store a new user on the system. Returns the created user and a HTTP/201
     * header on successful creation.
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->creationService->handle($request->validated());

        return $this->fractal->item($user)
            ->transformWith(UserTransformer::class)
            ->respond(201);
    }

    /**
     * Handle a request to delete a user from the Panel. Returns a HTTP/204 response
     * on successful deletion.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete(DeleteUserRequest $request, User $user): Response
    {
        $this->deletionService->handle($user);

        return $this->returnNoContent();
    }
}
