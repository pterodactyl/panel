<?php

namespace App\Transformers\Api\Application;

use App\Models\ServerVariable;
use App\Services\Acl\Api\AdminAcl;

class ServerVariableTransformer extends BaseTransformer
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = ['parent'];

    /**
     * Return the resource name for the JSONAPI output.
     *
     * @return string
     */
    public function getResourceName(): string
    {
        return ServerVariable::RESOURCE_NAME;
    }

    /**
     * Return a generic transformed server variable array.
     *
     * @param \App\Models\ServerVariable $variable
     * @return array
     */
    public function transform(ServerVariable $variable)
    {
        return $variable->toArray();
    }

    /**
     * Return the parent service variable data.
     *
     * @param \App\Models\ServerVariable $variable
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     * @throws \App\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeParent(ServerVariable $variable)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        $variable->loadMissing('variable');

        return $this->item($variable->getRelation('variable'), $this->makeTransformer(EggVariableTransformer::class), 'variable');
    }
}
