<?php

namespace Pterodactyl\Transformers\Api\Application;

use Cake\Chronos\Chronos;
use Pterodactyl\Models\ApiKey;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use League\Fractal\TransformerAbstract;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;

/**
 * @method array transform(Model $model)
 */
abstract class BaseTransformer extends TransformerAbstract
{
    const RESPONSE_TIMEZONE = 'UTC';

    /**
     * @var \Pterodactyl\Models\ApiKey
     */
    private $key;

    /**
     * Return the resource name for the JSONAPI output.
     *
     * @return string
     */
    abstract public function getResourceName(): string;

    /**
     * BaseTransformer constructor.
     */
    public function __construct()
    {
        // Transformers allow for dependency injection on the handle method.
        if (method_exists($this, 'handle')) {
            Container::getInstance()->call([$this, 'handle']);
        }
    }

    /**
     * Set the HTTP request class being used for this request.
     *
     * @param \Pterodactyl\Models\ApiKey $key
     *
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
     *
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
     * @param array $parameters
     *
     * @return \Pterodactyl\Transformers\Api\Application\BaseTransformer
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    protected function makeTransformer(string $abstract, array $parameters = [])
    {
        /** @var \Pterodactyl\Transformers\Api\Application\BaseTransformer $transformer */
        $transformer = Container::getInstance()->makeWith($abstract, $parameters);
        $transformer->setKey($this->getKey());

        if (! $transformer instanceof self) {
            throw new InvalidTransformerLevelException('Calls to ' . __METHOD__ . ' must return a transformer that is an instance of ' . __CLASS__);
        }

        return $transformer;
    }

    /**
     * Return an ISO-8601 formatted timestamp to use in the API response.
     *
     * @param string $timestamp
     *
     * @return string
     */
    protected function formatTimestamp(string $timestamp): string
    {
        return Chronos::createFromFormat(Chronos::DEFAULT_TO_STRING_FORMAT, $timestamp)
            ->setTimezone(self::RESPONSE_TIMEZONE)
            ->toIso8601String();
    }
}
