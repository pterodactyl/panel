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
 * @template TResource of Model|ServerState|array<mixed>
 *
 * @extends ApiResource<TResource>
 */
final class CallbackResource extends ApiResource
{
    /**
     * @param  TResource  $resource
     * @param  Closure(TResource): array<string, mixed>  $callback
     */
    public function __construct($resource, private readonly Closure $callback, private readonly ?string $name = null)
    {
        parent::__construct($resource);
    }

    /**
     * @template TData of Model|ServerState|array<mixed>
     *
     * @param  array<TData>|Collection<int, TData>|AbstractPaginator<int, TData>  $resource
     * @param  Closure(TData): array<string, mixed>  $callback
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

    public static function collection($resource): ApiResourceCollection
    {
        throw new LogicException('Build a list of callback resources with '.self::class.'::list().');
    }

    public function resourceName(): ?string
    {
        return $this->name;
    }

    public function toArray(Request $request): array
    {
        return ($this->callback)($this->resource);
    }
}
