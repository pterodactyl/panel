<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Subusers;

use Pterodactyl\Enum\Permissions;

class DeleteSubuserRequest extends SubuserRequest
{
    public function permission(): string
    {
        return Permissions::UserDelete->value;
    }
}
