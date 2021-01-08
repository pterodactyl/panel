<?php

namespace Pterodactyl\Http\Requests\Api\Application\Eggs;

use Pterodactyl\Models\Egg;

class UpdateEggRequest extends StoreEggRequest
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
        return $rules ?? Egg::getRulesForUpdate($this->route()->parameter('egg')->id);
    }
}
