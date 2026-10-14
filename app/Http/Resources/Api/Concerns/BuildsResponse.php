<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pterodactyl\Http\Resources\Api\Includes\IncludeSet;

trait BuildsResponse
{
    /** @var list<string> */
    private array $forcedIncludes = [];

    /** @var array<string, mixed> */
    private array $meta = [];

    private ?string $resourceKey = null;

    private bool $hasResourceKey = false;

    /**
     * Returns these includes whatever the request asked for.
     */
    public function withIncludes(string ...$includes): static
    {
        $this->forcedIncludes = [...$this->forcedIncludes, ...array_values($includes)];

        return $this;
    }

    /**
     * A key that is already set keeps its value.
     *
     * @param  array<string, mixed>  $meta
     */
    public function withMeta(array $meta): static
    {
        $this->meta += $meta;

        return $this;
    }

    /**
     * Null renders `"object": null`. Without a key a response names the objects after
     * resourceName(), and an include names them null.
     */
    public function withResourceKey(?string $resourceKey): static
    {
        $this->resourceKey = $resourceKey;
        $this->hasResourceKey = true;

        return $this;
    }

    public function respond(int $status = 200): JsonResponse
    {
        return new JsonResponse($this->resolve($this->resolveRequestFromContainer()), $status);
    }

    /**
     * Always 200: Laravel would answer 201 for a model created during the request.
     *
     * @param  Request  $request
     */
    final public function toResponse($request): JsonResponse
    {
        return new JsonResponse($this->resolve($request));
    }

    private function includeSet(Request $request): IncludeSet
    {
        return IncludeSet::fromRequest($request, $this->forcedIncludes);
    }
}
