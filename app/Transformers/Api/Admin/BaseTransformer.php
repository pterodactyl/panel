<?php

namespace Pterodactyl\Transformers\Api\Admin;

use Cake\Chronos\Chronos;
use Pterodactyl\Models\ApiKey;
use Illuminate\Container\Container;
use League\Fractal\TransformerAbstract;
use Pterodactyl\Services\Acl\Api\AdminAcl;

abstract class BaseTransformer extends TransformerAbstract
{
    const RESPONSE_TIMEZONE = 'UTC';

    /**
     * @var \Pterodactyl\Models\ApiKey
     */
    private $key;

    /**
     * Set the HTTP request class being used for this request.
     *
     * @param \Pterodactyl\Models\ApiKey $key
     * @return $this
     */
    public function setKey(ApiKey $key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Return the request instance being used for this transformer.
     *
     * @return \Pterodactyl\Models\ApiKey
     */
    public function getKey(): ApiKey
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

    /**
     * Return an ISO-8601 formatted timestamp to use in the API response.
     *
     * @param string $timestamp
     * @return string
     */
    protected function formatTimestamp(string $timestamp): string
    {
        return Chronos::createFromFormat(Chronos::DEFAULT_TO_STRING_FORMAT, $timestamp)
            ->setTimezone(self::RESPONSE_TIMEZONE)
            ->toIso8601String();
    }
}
