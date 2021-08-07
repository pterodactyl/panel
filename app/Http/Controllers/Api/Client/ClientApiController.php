<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Webmozart\Assert\Assert;
use Pterodactyl\Transformers\Api\Transformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

abstract class ClientApiController extends ApplicationApiController
{
    /**
     * Returns only the includes which are valid for the given transformer.
     *
     * @return string[]
     */
    protected function getIncludesForTransformer(Transformer $transformer, array $merge = []): array
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
    protected function parseIncludes(): array
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
     * @return \Pterodactyl\Transformers\Api\Transformer
     *
     * @deprecated
     */
    public function getTransformer(string $class)
    {
        $transformer = new $class;

        Assert::same(substr($class, 0, strlen(class_basename($class)) * -1), '\Pterodactyl\Transformers\Api\Client\\');

        return $transformer;
    }
}
