<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Users;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Users\CreatesUsers;
use Pterodactyl\Contracts\Users\DeletesUsers;
use Pterodactyl\Contracts\Users\UpdatesUsers;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\Scribe\Attributes\ExtensionFieldsParam;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Users\DeleteUserRequest;
use Pterodactyl\Http\Requests\Api\Admin\Users\GetUserRequest;
use Pterodactyl\Http\Requests\Api\Admin\Users\GetUsersRequest;
use Pterodactyl\Http\Requests\Api\Admin\Users\StoreUserRequest;
use Pterodactyl\Http\Requests\Api\Admin\Users\UpdateAvatarRequest;
use Pterodactyl\Http\Requests\Api\Admin\Users\UpdateUserRequest;
use Pterodactyl\Http\Resources\Api\Admin\UserResource;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Users\UserAvatarService;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Users', 'Create, update, retrieve, and delete panel users.')]
class UserController extends AdminApiController
{
    private const array DELETE_SELF_ERROR = [
        'errors' => [
            [
                'code' => 'DisplayException',
                'status' => '400',
                'detail' => 'You cannot delete your own account.',
            ],
        ],
    ];

    /**
     * List users.
     */
    #[Endpoint('List users', 'Returns a paginated list of panel users, sorted with root administrators first by default.')]
    #[QueryParam('page', 'integer', 'Page number to retrieve.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Results to return per page.', required: false, example: 50)]
    #[QueryParam('filter[email]', 'string', 'Filter users by email address.', required: false, example: 'admin@example.com')]
    #[QueryParam('filter[uuid]', 'string', 'Filter users by UUID.', required: false, example: '1b19cf3f-2f89-4f88-a81e-321e7fe326bc')]
    #[QueryParam('filter[username]', 'string', 'Filter users by username.', required: false, example: 'admin')]
    #[QueryParam('filter[external_id]', 'string', 'Filter users by external identifier.', required: false, example: 'remote-123')]
    #[QueryParam('filter[search]', 'string', 'Search users by username, email, or UUID.', required: false, example: 'admin')]
    #[QueryParam('sort', 'string', 'Sort users by id, uuid, email, username, or creation date. Prefix with a hyphen for descending order.', required: false, example: '-created_at')]
    #[ResponseFromResource(UserResource::class, User::class, description: 'Users returned.', collection: true, resourceKey: 'user', paginate: 50)]
    public function index(GetUsersRequest $request): ApiResourceCollection
    {
        $users = QueryBuilder::for(
            User::query()
                ->select('users.*')
                ->selectSub(
                    Subuser::query()
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('subusers.user_id', 'users.id'),
                    'subuser_of_count'
                )
                ->withCount('servers')
        )
            ->allowedFilters([
                'email',
                'uuid',
                'username',
                'external_id',
                AllowedFilter::callback('search', function (Builder $builder, string $value): void {
                    $builder->where(function (Builder $builder) use ($value): void {
                        $builder
                            ->where('users.username', 'LIKE', "%{$value}%")
                            ->orWhere('users.email', 'LIKE', "%{$value}%")
                            ->orWhere('users.uuid', 'LIKE', "%{$value}%");
                    });
                }),
            ])
            ->defaultSort('-root_admin')
            ->allowedSorts(['id', 'uuid', 'email', 'username', 'created_at'])
            ->paginate($request->perPage());

        return UserResource::collection($users);
    }

    /**
     * Show user.
     */
    #[Endpoint('Get user', 'Returns a single panel user by internal numeric ID.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports servers.', required: false, example: 'servers')]
    #[ResponseFromResource(UserResource::class, User::class, description: 'User returned.', resourceKey: 'user', include: ['servers'])]
    public function show(GetUserRequest $request, User $user): UserResource
    {
        return UserResource::make($user)->withExtensionFields();
    }

    /**
     * Create user.
     */
    #[Endpoint('Create user', 'Creates a panel user. Optional password and language fields may be supplied explicitly.')]
    #[ResponseFromResource(UserResource::class, User::class, status: 201, description: 'User created.', resourceKey: 'user', meta: ['resource' => 'https://panel.example.com/api/admin/users/1'])]
    #[ExtensionFieldsParam]
    public function store(StoreUserRequest $request, CreatesUsers $users): JsonResponse
    {
        $user = $users->create($request->actionInput());

        // The action logs the new account on the user's own feed.
        Activity::event('admin:user.create')
            ->property('email', $user->email)
            ->log();

        return UserResource::make($user)
            ->withExtensionFields()
            ->withMeta([
                'resource' => route('api.admin.users.view', [
                    'user' => $user->id,
                ]),
            ])
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Update user.
     */
    #[Endpoint('Update user', 'Updates an existing panel user. Supplying a password changes the user password.')]
    #[ResponseFromResource(UserResource::class, User::class, description: 'User updated.', resourceKey: 'user')]
    #[ExtensionFieldsParam]
    public function update(UpdateUserRequest $request, UpdatesUsers $users, User $user): UserResource
    {
        $user = $users->update($user, $request->actionInput());

        Activity::event('admin:user.update')
            ->subject($user)
            ->property('email', $user->email)
            ->log();

        return UserResource::make($user)->withExtensionFields();
    }

    /**
     * Update user avatar.
     */
    #[Endpoint('Update user avatar', "Stores the uploaded image as the user's avatar and deletes the one it replaces.")]
    #[BodyParam('avatar', 'file', 'PNG, JPEG, SVG, WebP, AVIF or ICO image of at most 10 MiB.', required: true)]
    #[ResponseFromResource(UserResource::class, User::class, description: 'Avatar updated.', resourceKey: 'user')]
    public function updateAvatar(UpdateAvatarRequest $request, UserAvatarService $avatars, User $user): UserResource
    {
        $avatars->replace($user, $request->file('avatar'));

        return UserResource::make($user->refresh())->withExtensionFields();
    }

    /**
     * Delete user avatar.
     */
    #[Endpoint('Delete user avatar', "Deletes the user's avatar.")]
    #[ScribeResponse(status: 204, description: 'Avatar deleted.')]
    public function deleteAvatar(UserAvatarService $avatars, User $user): Response
    {
        $avatars->clear($user);

        return response()->noContent();
    }

    /**
     * Delete user.
     */
    #[Endpoint('Delete user', 'Deletes a panel user. The authenticated administrator cannot delete their own account.')]
    #[ScribeResponse(status: 204, description: 'User deleted.')]
    #[ScribeResponse(self::DELETE_SELF_ERROR, status: 400, description: 'The authenticated administrator attempted to delete their own user account.')]
    public function destroy(DeleteUserRequest $request, DeletesUsers $users, User $user): Response
    {
        if ($request->user()->is($user)) {
            throw new DisplayException(__('admin/user.exceptions.delete_self'));
        }

        $users->delete($user);

        Activity::event('admin:user.delete')
            ->subject($user)
            ->property('email', $user->email)
            ->log();

        return $this->returnNoContent();
    }
}
