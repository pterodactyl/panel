<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pterodactyl\Http\Resources\Api\Includes\IncludeSet;

/**
 * What a controller sets on the resource it returns: includes it always returns, top-level
 * meta, the name of the rendered objects, and the status of the response.
 */
trait BuildsResponse
{
    /** @var list<string> */
    private array $forcedIncludes = [];

    /** @var ApiResourceMeta */
    private array $meta = [];

    private ?string $resourceKey = null;

    private bool $hasResourceKey = false;

    /**
     * Returns these includes whatever the request asked for, as Fractal's parseIncludes()
     * did when a controller called it.
     */
    public function withIncludes(string ...$includes): static
    {
        $this->forcedIncludes = [...$this->forcedIncludes, ...array_values($includes)];

        return $this;
    }

    /**
     * Adds top-level meta. A key that is already set keeps its value, as with Fractal's addMeta().
     *
     * @param  ApiResourceMeta  $meta
     */
    public function withMeta(array $meta): static
    {
        $this->meta += $meta;

        return $this;
    }

    /**
     * Names the rendered objects; null renders `"object": null`. Without it a response names
     * them after resourceName(), and an include names them null.
     */
    public function withResourceKey(?string $resourceKey): static
    {
        $this->resourceKey = $resourceKey;
        $this->hasResourceKey = true;

        return $this;
    }

    /**
     * The response with the given status, as Fractal's respond() built it.
     */
    public function respond(int $status = 200): JsonResponse
    {
        return new JsonResponse($this->resolve($this->resolveRequestFromContainer()), $status);
    }

    /**
     * A resource returned from a controller answers 200; Laravel would answer 201 for a model
     * created during the request, which Fractal never did.
     *
     * @param  Request  $request
     */
    final public function toResponse($request): JsonResponse
    {
        return new JsonResponse($this->resolve($request));
    }

    /**
     * The includes the request asked for, with the ones the controller always returns.
     */
    private function includeSet(Request $request): IncludeSet
    {
        return IncludeSet::fromRequest($request, $this->forcedIncludes);
    }
}
