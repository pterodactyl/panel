<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\Server;

class PullFileRequest extends ClientApiRequest
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

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'url', 'max:2048'],
            'directory' => ['nullable', 'string', 'max:2048'],
            'filename' => ['nullable', 'string', 'max:255'],
            'use_header' => ['boolean'],
            'foreground' => ['boolean'],
        ];
    }
}
