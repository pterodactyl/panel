<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\Server;

class UploadFileRequest extends ClientApiRequest
{
    /**
     * {@inheritdoc}
     */
    public function authorize(): bool
    {
        $server = $this->parameter('server', Server::class);

        return $this->user()->can(Permissions::FileCreate->value, $server)
            && $this->user()->can(Permissions::FileUpdate->value, $server);
    }
}
