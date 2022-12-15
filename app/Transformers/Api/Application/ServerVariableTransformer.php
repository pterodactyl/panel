<?php

namespace Pterodactyl\Transformers\Api\Application;

use League\Fractal\Resource\Item;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\ServerVariable;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;

class ServerVariableTransformer extends Transformer
{
    /**
     * List of resources that can be included.
     */
    protected array $availableIncludes = ['parent'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return ServerVariable::RESOURCE_NAME;
    }

    /**
     * Return a generic transformed server variable array.
     */
    public function transform(EggVariable $model): array
    {
        return $model->toArray();
    }

    /**
     * Return the parent service variable data.
     */
    public function includeParent(EggVariable $variable): Item|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        // TODO: what the fuck?
        return $this->item($variable->variable, new EggVariableTransformer());
    }
}
