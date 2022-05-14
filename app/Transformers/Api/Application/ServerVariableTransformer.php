<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\EggVariable;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class ServerVariableTransformer extends BaseTransformer
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
     *
     * @return array
     */
    public function transform(EggVariable $variable)
    {
        return $variable->toArray();
    }

    /**
     * Return the parent service variable data.
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeParent(EggVariable $variable)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        $variable->loadMissing('variable');

        return $this->item($variable->getRelation('variable'), $this->makeTransformer(EggVariableTransformer::class), 'variable');
    }
}
