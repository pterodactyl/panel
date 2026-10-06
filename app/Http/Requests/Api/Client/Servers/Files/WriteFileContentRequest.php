<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\Server;

class WriteFileContentRequest extends ClientApiRequest
{
    /**
     * {@inheritdoc}
     */
    public function authorize(): bool
    {
        $server = $this->parameter('server', Server::class);

        return $this->user()->can(Permissions::FileCreate->value, $server)
            || $this->user()->can(Permissions::FileUpdate->value, $server);
    }

    /**
     * There is no rule here for the file contents since we just use the body content
     * on the request to set the file contents. If nothing is passed that is fine since
     * it just means we want to set the file to be empty.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'string'],
        ];
    }
}
