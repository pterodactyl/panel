<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application\Users;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Contracts\Users\CreatesUsers;
use Pterodactyl\Contracts\Users\DeletesUsers;
use Pterodactyl\Contracts\Users\UpdatesUsers;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Users\DeleteUserRequest;
use Pterodactyl\Http\Requests\Api\Application\Users\GetUsersRequest;
use Pterodactyl\Http\Requests\Api\Application\Users\StoreUserRequest;
use Pterodactyl\Http\Requests\Api\Application\Users\UpdateUserRequest;
use Pterodactyl\Models\User;
use Pterodactyl\Transformers\Api\Application\UserTransformer;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

#[Group('Application API', 'Root administrator endpoints for managing panel resources using application API tokens.')]
#[Subgroup('Users', 'Create, update, retrieve, and delete panel users.')]
class UserController extends ApplicationApiController
{
    /**
     * Handle request to list all users on the panel. Returns a JSON-API representation
     * of a collection of users including any defined relations passed in
     * the request.
     *
     * @return ApiPayload
     */
    #[Endpoint('List users', 'Returns a paginated list of panel users visible to the application API key.')]
    #[QueryParam('per_page', 'integer', 'Number of users to return per page, up to 100.', required: false, example: 50)]
    #[QueryParam('filter[email]', 'string', 'Filter users by email address.', required: false, example: 'user@example.com')]
    #[QueryParam('filter[uuid]', 'string', 'Filter users by UUID.', required: false, example: '0d1f6f4d-9f78-4cf9-9d5a-6f7f4c9f4d6a')]
    #[QueryParam('filter[username]', 'string', 'Filter users by username.', required: false, example: 'example-user')]
    #[QueryParam('filter[external_id]', 'string', 'Filter users by external identifier.', required: false, example: 'billing-system-42')]
    #[QueryParam('sort', 'string', 'Sort users by id or uuid. Prefix with "-" for descending order.', required: false, example: '-id', enum: ['id', '-id', 'uuid', '-uuid'])]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "servers" when the key can read servers.', required: false, example: 'servers', enum: ['servers'])]
    #[ResponseFromTransformer(UserTransformer::class, User::class, collection: true, resourceKey: 'user', paginate: [IlluminatePaginatorAdapter::class, 50])]
    public function index(GetUsersRequest $request): array
    {
        $users = QueryBuilder::for(User::query())
            ->allowedFilters(['email', 'uuid', 'username', 'external_id'])
            ->allowedSorts(['id', 'uuid'])
            ->paginate($request->perPage());

        return Fractal::collection($users)
            ->transformWith($this->getTransformer(UserTransformer::class))
            ->toResponseArray();
    }

    /**
     * Handle a request to view a single user. Includes any relations that
     * were defined in the request.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get user', 'Returns a single panel user by internal numeric ID.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "servers" when the key can read servers.', required: false, example: 'servers', enum: ['servers'])]
    #[ResponseFromTransformer(UserTransformer::class, User::class, resourceKey: 'user')]
    public function view(GetUsersRequest $request, User $user): array
    {
        return Fractal::item($user)
            ->transformWith($this->getTransformer(UserTransformer::class))
            ->toResponseArray();
    }

    /**
     * @return ApiPayload
     *
     * @throws Throwable
     */
    #[Endpoint('Update user', 'Updates account details for an existing panel user.')]
    #[ResponseFromTransformer(UserTransformer::class, User::class, resourceKey: 'user')]
    public function update(UpdateUserRequest $request, UpdatesUsers $users, User $user): array
    {
        $user = $users->update($user, $request->payload());

        $response = Fractal::item($user)
            ->transformWith($this->getTransformer(UserTransformer::class));

        return $response->toResponseArray();
    }

    /**
     * Store a new user on the system. Returns the created user and an HTTP/201
     * header on successful creation.
     *
     * @throws Throwable
     */
    #[Endpoint('Create user', 'Creates a new panel user and returns the created resource.')]
    #[ResponseFromTransformer(UserTransformer::class, User::class, status: 201, description: 'User created.', resourceKey: 'user', meta: ['resource' => 'https://panel.example.test/api/application/users/1'])]
    public function store(StoreUserRequest $request, CreatesUsers $users): JsonResponse
    {
        $user = $users->create($request->payload());

        Activity::event('user:user.create')
            ->subject($user)
            ->property(['email' => $user->email, 'username' => $user->username, 'admin' => $user->root_admin])
            ->log();

        return Fractal::item($user)
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
     * @throws DisplayException
     */
    #[Endpoint('Delete user', 'Deletes a panel user by internal numeric ID.')]
    #[ScribeResponse(status: 204, description: 'User deleted.')]
    public function delete(DeleteUserRequest $request, DeletesUsers $users, User $user): JsonResponse
    {
        $users->delete($user);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
