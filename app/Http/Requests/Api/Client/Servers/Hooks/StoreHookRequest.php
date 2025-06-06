<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Hooks;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\Permission;

class StoreHookRequest extends ClientApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function permission(): string
    {
        // ACTION_HOOKS_READ,ACTION_HOOKS_CREATE,ACTION_HOOKS_UPDATE,ACTION_HOOKS_DELETE
        return Permission::ACTION_HOOKS_CREATE;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'server_id' => 'required|uuid|exists:servers,id',
            'name' => 'required|string',
            'enabled' => 'nullable|boolean',
            'triggers' => 'required|array',
            'actions' => 'required|array',
        ];
    }
}
