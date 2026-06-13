<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Webmozart\Assert\Assert;
use Pterodactyl\Transformers\Api\Client\BaseClientTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

abstract class ClientApiController extends ApplicationApiController
{
    /**
     * Returns only the includes which are valid for the given transformer.
     */
    protected function getIncludesForTransformer(BaseClientTransformer $transformer, array $merge = []): array
    {
        $filtered = array_filter($this->parseIncludes(), function ($datum) use ($transformer) {
            return in_array($datum, $transformer->getAvailableIncludes());
        });

        return array_merge($filtered, $merge);
    }

    /**
     * Returns the parsed includes for this request.
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
     * @template T of \Pterodactyl\Transformers\Api\Client\BaseClientTransformer
     *
     * @param class-string<T> $abstract
     *
     * @return T
     *
     * @noinspection PhpDocSignatureInspection
     */
    public function getTransformer(string $abstract)
    {
        Assert::subclassOf($abstract, BaseClientTransformer::class); // @phpstan-ignore staticMethod.alreadyNarrowedType

        return $abstract::fromRequest($this->request);
    }
}
