<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use LogicException;
use Pterodactyl\Data\ServerState;

/**
 * A resource whose attributes come from a closure, as when Fractal transformed with a
 * callable: it has no includes, and it is named only when given a name.
 *
 * @template TResource of ApiResourceData
 *
 * @extends ApiResource<TResource>
 */
final class CallbackResource extends ApiResource
{
    /**
     * @param  TResource  $resource
     * @param  Closure(TResource): ApiPayload  $callback
     */
    public function __construct($resource, private readonly Closure $callback, private readonly ?string $name = null)
    {
        parent::__construct($resource);
    }

    /**
     * A list of resources rendered by $callback, each named $name.
     *
     * @template TData of ApiResourceData
     *
     * @param  array<array-key, TData>|Collection<covariant array-key, covariant TData>|AbstractPaginator<covariant array-key, covariant TData>  $resource
     * @param  Closure(TData): ApiPayload  $callback
     */
    public static function list(array|Collection|AbstractPaginator $resource, Closure $callback, ?string $name = null): ApiResourceCollection
    {
        if ($resource instanceof AbstractPaginator) {
            return new ApiResourceCollection($resource->through(fn (Model|ServerState|array $item): self => new self($item, $callback, $name)), self::class);
        }

        $items = [];
        foreach ($resource as $item) {
            $items[] = new self($item, $callback, $name);
        }

        return new ApiResourceCollection($items, self::class);
    }

    /**
     * Items of a list need their closure; build the list with list().
     *
     * @param  array<array-key, TResource>|Collection<covariant array-key, covariant TResource>|AbstractPaginator<covariant array-key, covariant TResource>  $resource
     */
    public static function collection($resource): ApiResourceCollection
    {
        throw new LogicException('Build a list of callback resources with '.self::class.'::list().');
    }

    public function resourceName(): ?string
    {
        return $this->name;
    }

    /**
     * @return ApiPayload
     */
    public function toArray(Request $request): array
    {
        return ($this->callback)($this->resource);
    }
}
