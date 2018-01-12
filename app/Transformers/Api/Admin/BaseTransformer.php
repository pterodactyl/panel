<?php

namespace Pterodactyl\Transformers\Api\Admin;

use Pterodactyl\Models\APIKey;
use Illuminate\Container\Container;
use League\Fractal\TransformerAbstract;
use Pterodactyl\Services\Acl\Api\AdminAcl;

abstract class BaseTransformer extends TransformerAbstract
{
    /**
     * @var \Pterodactyl\Models\APIKey
     */
    private $key;

    /**
     * Set the HTTP request class being used for this request.
     *
     * @param \Pterodactyl\Models\APIKey $key
     * @return $this
     */
    public function setKey(APIKey $key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Return the request instance being used for this transformer.
     *
     * @return \Pterodactyl\Models\APIKey
     */
    public function getKey(): APIKey
    {
        return $this->key;
    }

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
     * @return \Pterodactyl\Transformers\Api\Admin\BaseTransformer
     */
    protected function makeTransformer(string $abstract, array $parameters = []): self
    {
        /** @var \Pterodactyl\Transformers\Api\Admin\BaseTransformer $transformer */
        $transformer = Container::getInstance()->makeWith($abstract, $parameters);
        $transformer->setKey($this->getKey());

        return $transformer;
    }
}
