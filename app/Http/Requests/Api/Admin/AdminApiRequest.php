<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\ApiRequest;
use Pterodactyl\Models\User;

/**
 * @method User user($guard = null)
 */
abstract class AdminApiRequest extends ApiRequest
{
    /**
     * The permissions required to perform this request. Root administrators are granted
     * everything (see AuthServiceProvider); these are checked against the requesting user.
     *
     * @return Permissions[]
     */
    abstract public function permissions(): array;

    public function authorize(): bool
    {
        $permissions = $this->permissions();

        // A request that declares no specific permissions still requires root administrator access.
        if ($permissions === []) {
            return (bool) $this->user()->root_admin;
        }

        $abilities = array_map(fn (Permissions $permission) => $permission->value, $permissions);

        return $this->user()->can($abilities);
    }
}
