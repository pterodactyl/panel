<?php

namespace Pterodactyl\Http\Requests\Api\Application\Eggs;

class UpdateEggRequest extends StoreEggRequest
{
    public function rules(array $rules = null): array
    {
        return [
            'nest_id' => 'sometimes|numeric|exists:nests,id',
            'name' => 'sometimes|string|max:191',
            'description' => 'sometimes|string|nullable',
            'features' => 'sometimes|array',
            'docker_images' => 'sometimes|array|min:1',
            'docker_images.*' => 'sometimes|string',
            'file_denylist' => 'sometimes|array|nullable',
            'file_denylist.*' => 'sometimes|string',
            'config_files' => 'sometimes|nullable|json',
            'config_startup' => 'sometimes|nullable|json',
            'config_stop' => 'sometimes|nullable|string|max:191',
//            'config_from' => 'sometimes|nullable|numeric|exists:eggs,id',
            'startup' => 'sometimes|string',
            'script_container' => 'sometimes|string',
            'script_entry' => 'sometimes|string',
            'script_install' => 'sometimes|string',
        ];
    }
}
