<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Transformers\Api\Application\BaseTransformer;
use UnexpectedValueException;

abstract class ApplicationApiController extends Controller
{
    /**
     * Return an instance of an application transformer.
     *
     * @template T of \Pterodactyl\Transformers\Api\Application\BaseTransformer
     *
     * @param  class-string<T>  $abstract
     * @return T
     *
     * @noinspection PhpDocSignatureInspection
     */
    public function getTransformer(string $abstract): BaseTransformer
    {
        return $this->makeTransformer($abstract, BaseTransformer::class);
    }

    /**
     * Resolve a transformer through the container, asserting it extends the API's base transformer.
     *
     * @template T of \Pterodactyl\Transformers\Api\Application\BaseTransformer
     *
     * @param  class-string<T>  $abstract
     * @param  class-string<BaseTransformer>  $base
     * @return T
     */
    protected function makeTransformer(string $abstract, string $base): BaseTransformer
    {
        throw_unless(is_subclass_of($abstract, $base), InvalidArgumentException::class, "Transformer [$abstract] must extend ".$base.'.');

        $transformer = App::make($abstract);
        throw_unless($transformer instanceof $abstract, UnexpectedValueException::class, "The container did not resolve transformer [$abstract].");

        return $transformer;
    }

    /**
     * Return an HTTP/204 response for the API.
     */
    protected function returnNoContent(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
