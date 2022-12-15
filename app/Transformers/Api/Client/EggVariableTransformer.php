<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\EggVariable;
use Pterodactyl\Transformers\Api\Transformer;

class EggVariableTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return EggVariable::RESOURCE_NAME;
    }

    public function transform(EggVariable $model): array
    {
        // This guards against someone incorrectly retrieving variables (haha, me) and then passing
        // them into the transformer and along to the user. Just throw an exception and break the entire
        // pathway since you should never be exposing these types of variables to a client.
        if (!$model->user_viewable) {
            throw new \BadMethodCallException('Cannot transform a hidden egg variable in a client transformer.');
        }

        return [
            'name' => $model->name,
            'description' => $model->description,
            'env_variable' => $model->env_variable,
            'default_value' => $model->default_value,
            'server_value' => $model->server_value,
            'is_editable' => $model->user_editable,
            'rules' => $model->rules,
        ];
    }
}
