<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Subusers;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Services\Servers\GetUserPermissionsService;

abstract class SubuserRequest extends ClientApiRequest
{
    protected ?Subuser $model;

    /**
     * Authorize the request and ensure that a user is not trying to modify themselves.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function authorize(): bool
    {
        if (!parent::authorize()) {
            return false;
        }

        $user = $this->route()->parameter('user');
        // Don't allow a user to edit themselves on the server.
        if ($user instanceof User) {
            if ($user->uuid === $this->user()->uuid) {
                return false;
            }
        }

        // If this is a POST request, validate that the user can even assign the permissions they
        // have selected to assign.
        if ($this->method() === Request::METHOD_POST && $this->has('permissions')) {
            $this->validatePermissionsCanBeAssigned(
                $this->input('permissions') ?? []
            );
        }

        return true;
    }

    /**
     * Validates that the permissions we are trying to assign can actually be assigned
     * by the user making the request.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function validatePermissionsCanBeAssigned(array $permissions)
    {
        $user = $this->user();
        /** @var \Pterodactyl\Models\Server $server */
        $server = $this->route()->parameter('server');

        // If we are a root admin or the server owner, no need to perform these checks.
        if ($user->root_admin || $user->id === $server->owner_id) {
            return;
        }

        // Otherwise, get the current subuser's permission set, and ensure that the
        // permissions they are trying to assign are not _more_ than the ones they
        // already have.
        /** @var \Pterodactyl\Models\Subuser|null $subuser */
        /** @var \Pterodactyl\Services\Servers\GetUserPermissionsService $service */
        $service = $this->container->make(GetUserPermissionsService::class);

        if (count(array_diff($permissions, $service->handle($server, $user))) > 0) {
            throw new HttpForbiddenException('Cannot assign permissions to a subuser that your account does not actively possess.');
        }
    }
}
