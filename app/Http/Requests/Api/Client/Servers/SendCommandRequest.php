<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers;

use Pterodactyl\Models\Server;

class SendCommandRequest extends GetServerRequest
{
    /**
     * Determine if the API user has permission to perform this action.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('send-command', $this->getModel(Server::class));
    }

    /**
     * Rules to validate this request against.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'command' => 'required|string|min:1',
        ];
    }
}
