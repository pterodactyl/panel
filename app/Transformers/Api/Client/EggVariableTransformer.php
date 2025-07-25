<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\EggVariable;

class EggVariableTransformer extends BaseClientTransformer
{
    public function getResourceName(): string
    {
        return EggVariable::RESOURCE_NAME;
    }

    public function transform(EggVariable $variable): array
    {
        // This guards against someone incorrectly retrieving variables (haha, me) and then passing
        // them into the transformer and along to the user. Just throw an exception and break the entire
        // pathway since you should never be exposing these types of variables to a client.
        if (!$variable->user_viewable) {
            throw new \BadMethodCallException('Cannot transform a hidden egg variable in a client transformer.');
        }

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
