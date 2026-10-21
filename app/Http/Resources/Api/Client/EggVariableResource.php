<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Client;

use BadMethodCallException;
use Illuminate\Http\Request;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\EggVariable;

/**
 * @extends ClientResource<EggVariable>
 */
#[ResponseField('options', schema: ['type' => 'array', 'items' => ['type' => 'object', 'required' => ['label', 'value'], 'properties' => ['label' => ['type' => 'string'], 'value' => ['type' => 'string']]], 'example' => [['label' => '1.21.11', 'value' => '1.21.11']]])]
#[ResponseField('has_options_source', 'boolean', example: true)]
#[ResponseField('allow_manual_input', 'boolean', example: true)]
class EggVariableResource extends ClientResource
{
    public function resourceName(): string
    {
        return EggVariable::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        // This guards against someone incorrectly retrieving variables (haha, me) and then passing
        // them into the resource and along to the user. Just throw an exception and break the entire
        // pathway since you should never be exposing these types of variables to a client.
        throw_unless($this->resource->user_viewable, BadMethodCallException::class, 'Cannot transform a hidden egg variable in a client resource.');

        return [
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'env_variable' => $this->resource->env_variable,
            'default_value' => $this->resource->default_value,
            'server_value' => $this->resource->server_value ?? null,
            'is_editable' => $this->resource->user_editable,
            'rules' => $this->resource->rules,
            'options' => $this->resource->getAttribute('resolved_options') ?? [],
            'has_options_source' => is_array($this->resource->options_source),
            'allow_manual_input' => (bool) ($this->resource->options_source['allow_manual_input'] ?? true),
        ];
    }
}
