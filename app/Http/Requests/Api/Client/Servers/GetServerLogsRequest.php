<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers;

use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Contracts\Servers\ReadsServerLogs;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class GetServerLogsRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    /**
     * Console output is what Wings streams to every websocket connection, so
     * reading it over HTTP requires the same permission as connecting.
     */
    public function permission(): string
    {
        return Permissions::WebsocketConnect->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'lines' => ['sometimes', 'integer', 'min:1', 'max:'.ReadsServerLogs::MAX_LINES],
        ];
    }
}
