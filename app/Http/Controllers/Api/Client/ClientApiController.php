<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client;

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
}
