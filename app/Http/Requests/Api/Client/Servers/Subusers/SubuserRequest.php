<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Subusers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Servers\GetUserPermissionsService;
use Pterodactyl\Services\Servers\SubuserPermissionCatalog;
use UnexpectedValueException;

abstract class SubuserRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    protected ?Subuser $model = null;

    /**
     * Authorize the request and ensure that a user is not trying to modify themselves.
     *
     * @throws BindingResolutionException
     */
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $route = $this->route();
        throw_if($route === null, UnexpectedValueException::class, 'The subuser request does not have an active route.');

        $user = $route->parameter('user');
        // Don't allow a user to edit themselves on the server.
        if ($user instanceof User) {
            if ($user->uuid === $this->user()->uuid) {
                return false;
            }
        }

        // If this is a POST request, validate that the user can even assign the permissions they
        // have selected to assign.
        if ($this->method() === Request::METHOD_POST && $this->has('permissions')) {
            $this->validatePermissionsCanBeAssigned($this->permissionInput());
        }

        return true;
    }

    public function subuser(): Subuser
    {
        $subuser = $this->attributes->get('subuser');
        throw_unless($subuser instanceof Subuser, UnexpectedValueException::class, 'The subuser request is missing its subuser attribute.');

        return $subuser;
    }

    /**
     * The permissions to store for the subuser: the submitted keys the panel or an enabled
     * extension knows about, always including websocket access.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        $allowed = $this->container->make(SubuserPermissionCatalog::class)->keys();

        $cleaned = array_intersect($this->permissionInput(), $allowed);

        return array_values(array_unique(array_merge($cleaned, [Permissions::WebsocketConnect->value])));
    }

    /**
     * Validates that the permissions we are trying to assign can actually be assigned
     * by the user making the request.
     *
     * @param  list<string>  $permissions  unvalidated input, this runs before validation
     *
     * @throws BindingResolutionException
     */
    protected function validatePermissionsCanBeAssigned(array $permissions): void
    {
        // If we are a root admin or the server owner, no need to perform these checks.
        if ($this->requesterHasFullAccess()) {
            return;
        }

        $allowed = $this->requesterPermissions();
        $target = $this->attributes->get('subuser');
        if ($target instanceof Subuser) {
            $allowed = array_merge($allowed, $target->permissions);
        }

        throw_if(count(array_diff($permissions, $allowed)) > 0, HttpForbiddenException::class, 'Cannot assign permissions to a subuser that your account does not actively possess.');
    }

    /**
     * Whether the requester is a root admin or the server owner, who may manage any subuser.
     */
    protected function requesterHasFullAccess(): bool
    {
        $user = $this->user();

        return $user->root_admin || $user->id === $this->parameter('server', Server::class)->owner_id;
    }

    /**
     * The permissions the requester holds on this server as a subuser.
     *
     * @return list<string>
     *
     * @throws BindingResolutionException
     */
    protected function requesterPermissions(): array
    {
        $service = $this->container->make(GetUserPermissionsService::class);

        return array_values($service->handle($this->parameter('server', Server::class), $this->user()));
    }

    /** @return list<string> */
    private function permissionInput(): array
    {
        $value = $this->input('permissions', []);
        if (! is_array($value) || ! array_is_list($value)) {
            return [];
        }

        $permissions = [];
        foreach ($value as $permission) {
            if (is_string($permission)) {
                $permissions[] = $permission;
            }
        }

        return $permissions;
    }
}
