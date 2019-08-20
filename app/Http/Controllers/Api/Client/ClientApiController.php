<?php

namespace App\Http\Controllers\Api\Client;

use Webmozart\Assert\Assert;
use Illuminate\Container\Container;
use App\Transformers\Api\Client\BaseClientTransformer;
use App\Http\Controllers\Api\Application\ApplicationApiController;

abstract class ClientApiController extends ApplicationApiController
{
    /**
     * Return an instance of an application transformer.
     *
     * @param string $abstract
     * @return \App\Transformers\Api\Client\BaseClientTransformer
     */
    public function getTransformer(string $abstract)
    {
        /** @var \App\Transformers\Api\Client\BaseClientTransformer $transformer */
        $transformer = Container::getInstance()->make($abstract);
        Assert::isInstanceOf($transformer, BaseClientTransformer::class);

        $transformer->setKey($this->request->attributes->get('api_key'));
        $transformer->setUser($this->request->user());

        return $transformer;
    }
}
