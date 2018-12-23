<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class SendPowerRequest extends ClientApiRequest
{
    /**
     * Determine if the user has permission to send a power command to a server.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('power-' . $this->input('signal', '_undefined'), $this->getModel(Server::class));
    }

    /**
     * Rules to validate this request against.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'signal' => 'required|string|in:start,stop,restart,kill',
        ];
    }
}
