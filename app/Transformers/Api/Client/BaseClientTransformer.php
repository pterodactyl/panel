<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Transformers\Api\Application\BaseTransformer as BaseApplicationTransformer;

abstract class BaseClientTransformer extends BaseApplicationTransformer
{
    /**
     * Determine if the API key loaded onto the transformer has permission
     * to access a different resource. This is used when including other
     * models on a transformation request.
     *
     * @param string $resource
     * @return bool
     */
    protected function authorize(string $resource): bool
    {
        return AdminAcl::check($this->getKey(), $resource, AdminAcl::READ);
    }

    /**
     * Create a new instance of the transformer and pass along the currently
     * set API key.
     *
     * @param string $abstract
     * @param array  $parameters
     * @return self
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    protected function makeTransformer(string $abstract, array $parameters = [])
    {
        $transformer = parent::makeTransformer($abstract, $parameters);

        if (! $transformer instanceof self) {
            throw new InvalidTransformerLevelException('Calls to ' . __METHOD__ . ' must return a transformer that is an instance of ' . __CLASS__);
        }

        return $transformer;
    }
}
