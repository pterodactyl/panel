<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers;

use Illuminate\Validation\Rule;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\Task;

class SendPowerRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    /**
     * Determine if the user has permission to send a power command to a server.
     */
    public function permission(): string
    {
        return Task::permissionForAction(Task::ACTION_POWER, $this->string('signal')->toString())->value ?? '__invalid';
    }

    /**
     * Rules to validate this request against.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'signal' => ['required', 'string', Rule::in(Task::POWER_ACTIONS)],
        ];
    }
}
