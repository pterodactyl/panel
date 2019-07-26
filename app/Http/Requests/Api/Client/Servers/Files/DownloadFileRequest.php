<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Models\Server;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class DownloadFileRequest extends ClientApiRequest
{
    /**
     * Ensure that the user making this request has permission to download files
     * from this server.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('download-files', $this->getModel(Server::class));
    }
}
