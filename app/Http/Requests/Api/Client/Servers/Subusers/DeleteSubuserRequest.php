<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Subusers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;

class DeleteSubuserRequest extends SubuserRequest
{
    public function permission(): string
    {
        return Permissions::UserDelete->value;
    }

    /**
     * @throws BindingResolutionException
     */
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        if ($this->requesterHasFullAccess()) {
            return true;
        }

        $exceeding = array_diff($this->subuser()->permissions, $this->requesterPermissions(), [Permissions::WebsocketConnect->value]);

        throw_if(count($exceeding) > 0, HttpForbiddenException::class, 'Cannot remove a subuser that has permissions your account does not actively possess.');

        return true;
    }
}
