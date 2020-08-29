<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\EggVariable;

class EggVariableTransformer extends BaseClientTransformer
{
    /**
     * @return string
     */
    public function getResourceName(): string
    {
        return EggVariable::RESOURCE_NAME;
    }

    /**
     * @param \Pterodactyl\Models\EggVariable $variable
     * @return array
     */
    public function transform(EggVariable $variable)
    {
        return [
            'name' => $variable->name,
            'description' => $variable->description,
            'env_variable' => $variable->env_variable,
            'default_value' => $variable->default_value,
            'server_value' => $variable->server_value,
            'is_editable' => $variable->user_editable,
            'rules' => $variable->rules,
        ];
    }
}
