<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Users;

use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class DeleteUserRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_USERS;

    protected int $permission = AdminAcl::WRITE;

    public function authorize(): bool
    {
        return parent::authorize() && (! $this->parameter('user', User::class)->root_admin || $this->canManageAdministrators());
    }
}
