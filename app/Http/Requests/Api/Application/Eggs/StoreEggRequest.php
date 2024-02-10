<?php

namespace Pterodactyl\Http\Requests\Api\Application\Eggs;

use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreEggRequest extends ApplicationApiRequest
{
    public function rules(array $rules = null): array
    {
        return [
            'nest_id' => 'required|bail|numeric|exists:nests,id',
            'name' => 'required|string|max:191',
            'description' => 'sometimes|string|nullable',
            'features' => 'sometimes|array',
            'docker_images' => 'required|array|min:1',
            'docker_images.*' => 'required|string',
            'file_denylist' => 'sometimes|array|nullable',
            'file_denylist.*' => 'sometimes|string',
            'config_files' => 'required|nullable|json',
            'config_startup' => 'required|nullable|json',
            'config_stop' => 'required|nullable|string|max:191',
//            'config_from' => 'sometimes|nullable|numeric|exists:eggs,id',
            'startup' => 'required|string',
            'script_container' => 'sometimes|string',
            'script_entry' => 'sometimes|string',
            'script_install' => 'sometimes|string',
        ];
    }
}
