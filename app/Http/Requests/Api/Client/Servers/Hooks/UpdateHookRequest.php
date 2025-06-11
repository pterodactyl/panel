<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Hooks;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\Permission;

class UpdateHookRequest extends ClientApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function permission(): string
    {
        // ACTION_HOOKS_READ,ACTION_HOOKS_CREATE,ACTION_HOOKS_UPDATE,ACTION_HOOKS_DELETE
        return Permission::ACTION_HOOKS_UPDATE;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'enabled' => 'nullable|boolean',
            'trigger' => 'required|array',
            'action' => 'nullable|array',
        ];
    }
}
