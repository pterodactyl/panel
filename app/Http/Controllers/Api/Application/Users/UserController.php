<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Users;

use Pterodactyl\Models\User;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Services\Users\UserDeletionService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Transformers\Api\Application\UserTransformer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Pterodactyl\Http\Requests\Api\Application\Users\GetUserRequest;
use Pterodactyl\Http\Requests\Api\Application\Users\GetUsersRequest;
use Pterodactyl\Http\Requests\Api\Application\Users\StoreUserRequest;
use Pterodactyl\Http\Requests\Api\Application\Users\DeleteUserRequest;
use Pterodactyl\Http\Requests\Api\Application\Users\UpdateUserRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class UserController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Services\Users\UserCreationService
     */
    private $creationService;

    /**
     * @var \Pterodactyl\Services\Users\UserDeletionService
     */
    private $deletionService;

    /**
     * @var \Pterodactyl\Services\Users\UserUpdateService
     */
    private $updateService;

    /**
     * UserController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     * @param \Pterodactyl\Services\Users\UserCreationService $creationService
     * @param \Pterodactyl\Services\Users\UserDeletionService $deletionService
     * @param \Pterodactyl\Services\Users\UserUpdateService $updateService
     */
    public function __construct(
        UserRepositoryInterface $repository,
        UserCreationService $creationService,
        UserDeletionService $deletionService,
        UserUpdateService $updateService
    ) {
        parent::__construct();

        $this->repository = $repository;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->updateService = $updateService;
    }

    /**
     * Handle request to list all users on the panel. Returns a JSON-API representation
     * of a collection of users including any defined relations passed in
     * the request.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Users\GetUsersRequest $request
     *
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(GetUsersRequest $request): array
    {
        $perPage = $request->query('per_page', 10);
        if ($perPage < 1) {
            $perPage = 10;
        } else if ($perPage > 100) {
            throw new BadRequestHttpException('"per_page" query parameter must be below 100.');
        }

        $users = QueryBuilder::for(User::query())
            ->allowedFilters(['email', 'uuid', 'username', 'external_id'])
            ->allowedSorts(['id', 'uuid'])
            ->paginate($perPage);

        return $this->fractal->collection($users)
            ->transformWith($this->getTransformer(UserTransformer::class))
            ->toArray();
    }

    /**
     * Handle a request to view a single user. Includes any relations that
     * were defined in the request.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Users\GetUserRequest $request
     * @param \Pterodactyl\Models\User $user
     *
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function view(GetUserRequest $request, User $user): array
    {
        return $this->fractal->item($user)
            ->transformWith($this->getTransformer(UserTransformer::class))
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Users\UpdateUserRequest $request
     * @param \Pterodactyl\Models\User $user
     *
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function update(UpdateUserRequest $request, User $user): array
    {
        $this->updateService->setUserLevel(User::USER_LEVEL_ADMIN);
        $user = $this->updateService->handle($user, $request->validated());

        return $this->fractal->item($user)
            ->transformWith($this->getTransformer(UserTransformer::class))
            ->toArray();
    }

    /**
     * Store a new user on the system. Returns the created user and a HTTP/201
     * header on successful creation.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Users\StoreUserRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->creationService->handle($request->validated());

        return $this->fractal->item($user)
            ->transformWith($this->getTransformer(UserTransformer::class))
            ->addMeta([
                'resource' => route('api.application.users.view', [
                    'user' => $user->id,
                ]),
            ])
            ->respond(201);
    }

    /**
     * Handle a request to delete a user from the Panel. Returns a HTTP/204 response
     * on successful deletion.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Users\DeleteUserRequest $request
     * @param \Pterodactyl\Models\User $user
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete(DeleteUserRequest $request, User $user): JsonResponse
    {
        $this->deletionService->handle($user);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
