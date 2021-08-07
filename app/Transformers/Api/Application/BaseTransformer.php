<?php

namespace Pterodactyl\Transformers\Api\Application;

use Illuminate\Container\Container;
use Pterodactyl\Transformers\Api\Transformer;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;

abstract class BaseTransformer extends Transformer
{
    /**
     * Create a new instance of the transformer and pass along the currently
     * set API key.
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

        if (!$transformer instanceof self) {
            throw new InvalidTransformerLevelException('Calls to ' . __METHOD__ . ' must return a transformer that is an instance of ' . __CLASS__);
        }

        return $transformer;
    }
}
