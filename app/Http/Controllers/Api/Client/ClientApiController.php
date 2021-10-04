<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Webmozart\Assert\Assert;
use Illuminate\Container\Container;
use Pterodactyl\Transformers\Daemon\BaseDaemonTransformer;
use Pterodactyl\Transformers\Api\Client\BaseClientTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

abstract class ClientApiController extends ApplicationApiController
{
    /**
     * Returns only the includes which are valid for the given transformer.
     *
     * @return string[]
     */
    protected function getIncludesForTransformer(BaseClientTransformer $transformer, array $merge = [])
    {
        $filtered = array_filter($this->parseIncludes(), function ($datum) use ($transformer) {
            return in_array($datum, $transformer->getAvailableIncludes());
        });

        return array_merge($filtered, $merge);
    }

    /**
     * Returns the parsed includes for this request.
     *
     * @return string[]
     */
    protected function parseIncludes()
    {
        $includes = $this->request->query('include') ?? [];

        if (!is_string($includes)) {
            return $includes;
        }

        return array_map(function ($item) {
            return trim($item);
        }, explode(',', $includes));
    }

    /**
     * Return an instance of an application transformer.
     *
     * @return \Pterodactyl\Transformers\Api\Client\BaseClientTransformer
     */
    public function getTransformer(string $abstract)
    {
        /** @var \Pterodactyl\Transformers\Api\Client\BaseClientTransformer $transformer */
        $transformer = Container::getInstance()->make($abstract);
        Assert::isInstanceOfAny($transformer, [
            BaseClientTransformer::class,
            BaseDaemonTransformer::class,
        ]);

        if ($transformer instanceof BaseClientTransformer) {
            $transformer->setKey($this->request->attributes->get('api_key'));
            $transformer->setUser($this->request->user());
        }

        return $transformer;
    }
}
