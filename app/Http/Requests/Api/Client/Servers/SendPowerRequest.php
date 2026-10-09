<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers;

use Pterodactyl\Models\Task;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class SendPowerRequest extends ClientApiRequest
{
    /**
     * Determine if the user has permission to send a power command to a server.
     */
    public function permission(): string
    {
        return Task::permissionForAction(Task::ACTION_POWER, $this->input('signal')) ?? '__invalid';
    }

    /**
     * Rules to validate this request against.
     */
    public function rules(): array
    {
        return [
            'signal' => 'required|string|in:' . implode(',', Task::POWER_ACTIONS),
        ];
    }
}
