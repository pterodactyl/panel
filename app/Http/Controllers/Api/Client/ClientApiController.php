<?php

namespace Pterodactyl\Http\Controllers\Api\Application;

use Illuminate\Container\Container;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;

abstract class ClientApiController extends ApplicationApiController
{
    /**
     * Return an instance of an application transformer.
     *
     * @param string $abstract
     * @return \Pterodactyl\Transformers\Api\Client\BaseClientTransformer
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function getTransformer(string $abstract)
    {
        /** @var \Pterodactyl\Transformers\Api\Client\BaseClientTransformer $transformer */
        $transformer = Container::getInstance()->make($abstract);
        $transformer->setKey($this->request->attributes->get('api_key'));

        if (! $transformer instanceof self) {
            throw new InvalidTransformerLevelException('Calls to ' . __METHOD__ . ' must return a transformer that is an instance of ' . __CLASS__);
        }

        return $transformer;
    }
}
