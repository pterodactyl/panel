<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client;

use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Concerns\ResolvesRequestContext;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Transformers\Api\Client\BaseClientTransformer;

abstract class ClientApiController extends ApplicationApiController
{
    use ResolvesRequestContext;

    /**
     * Return an instance of an application transformer.
     *
     * @template T of \Pterodactyl\Transformers\Api\Client\BaseClientTransformer
     *
     * @param  class-string<T>  $abstract
     * @return T
     *
     * @noinspection PhpDocSignatureInspection
     */
    public function getTransformer(string $abstract): BaseClientTransformer
    {
        return $this->makeTransformer($abstract, BaseClientTransformer::class);
    }

    /**
     * Returns only the includes which are valid for the given transformer.
     *
     * @param  list<string>  $merge
     * @return list<string>
     */
    protected function getIncludesForTransformer(BaseClientTransformer $transformer, array $merge = []): array
    {
        $filtered = array_filter(
            $this->parseIncludes(),
            fn (string $datum): bool => in_array($datum, $transformer->getAvailableIncludes(), true),
        );

        return array_merge($filtered, $merge);
    }

    /**
     * Returns the parsed includes for this request.
     *
     * @return list<string>
     */
    protected function parseIncludes(): array
    {
        return Fractal::requestedIncludes();
    }
}
