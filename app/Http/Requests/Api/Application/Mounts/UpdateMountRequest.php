<?php

namespace Pterodactyl\Http\Requests\Api\Application\Mounts;

use Pterodactyl\Models\Mount;

class UpdateMountRequest extends StoreMountRequest
{
    /**
     * ?
     *
     * @param array|null $rules
     *
     * @return array
     */
    public function rules(array $rules = null): array
    {
        return $rules ?? Mount::getRulesForUpdate($this->route()->parameter('mount')->id);
    }
}
