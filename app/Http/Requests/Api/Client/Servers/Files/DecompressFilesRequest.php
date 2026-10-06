<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\Server;

class DecompressFilesRequest extends ClientApiRequest
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
            'root' => ['sometimes', 'nullable', 'string'],
            'file' => ['required', 'string'],
        ];
    }
}
