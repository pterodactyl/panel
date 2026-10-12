<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Pterodactyl\Http\Resources\Api\Concerns\BuildsResponse;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\Includes\IncludeScope;
use Pterodactyl\Http\Resources\Api\Includes\IncludeSet;
use Pterodactyl\Support\JsonEmptyObject;

/**
 * Keys are dropped. At the root of a response a length-aware paginator adds `pagination`
 * meta after any other meta.
 */
class ApiResourceCollection extends AnonymousResourceCollection implements IncludedResource
{
    use BuildsResponse;

    /** @var string|null */
    public static $wrap;

    /** @var class-string<ApiResource<*>> */
    public $collects;

    /** @var Collection<array-key, ApiResource<*>> */
    public $collection;

    /** @var Collection<array-key, ApiResource<*>>|AbstractPaginator<array-key, ApiResource<*>> */
    public $resource;

    /** @var array<string, mixed>|null */
    private ?array $included = null;

    /**
     * @param  class-string<ApiResource<*>>  $collects
     */
    public function __construct($resource, $collects)
    {
        parent::__construct($resource, $collects);
    }

    /**
     * @param  Request|null  $request
     * @return array<string, mixed>
     */
    final public function resolve($request = null): array
    {
        $request ??= $this->resolveRequestFromContainer();
        $includes = $this->includeSet($request);

        $this->prepare($includes->root(), $request);

        return $this->resolveFor($includes, $request);
    }

    /**
     * Without the eager loading resolve() batches first, as documentation examples render.
     *
     * @return array<string, mixed>
     */
    final public function resolveFor(IncludeSet $includes, Request $request): array
    {
        $list = $this->render($includes->root(), $request);

        $meta = $this->meta;
        if ($this->resource instanceof LengthAwarePaginator) {
            $meta['pagination'] = $this->pagination($this->resource);
        }

        return $meta === [] ? $list : $list + ['meta' => $meta];
    }

    final public function resolveIncluded(IncludeScope $scope, Request $request): static
    {
        $this->included = $this->render($scope, $request);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    final public function jsonSerialize(): array
    {
        return $this->included ?? $this->resolve($this->resolveRequestFromContainer());
    }

    /**
     * @param  LengthAwarePaginator<array-key, ApiResource<*>>  $paginator
     * @return array<string, mixed>
     */
    private function pagination(LengthAwarePaginator $paginator): array
    {
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();

        $links = [];
        if ($currentPage > 1) {
            $links['previous'] = $paginator->url($currentPage - 1);
        }

        if ($currentPage < $lastPage) {
            $links['next'] = $paginator->url($currentPage + 1);
        }

        // A total given as a string still renders as a number.
        return [
            'total' => (int) $paginator->total(),
            'count' => count($paginator->items()),
            'per_page' => (int) $paginator->perPage(),
            'current_page' => (int) $currentPage,
            'total_pages' => (int) $lastPage,
            'links' => $links === [] ? new JsonEmptyObject : $links,
        ];
    }

    private function prepare(IncludeScope $scope, Request $request): void
    {
        $models = new ModelCollection;
        foreach ($this->collection as $item) {
            if (! $item->resource instanceof Model) {
                return;
            }

            $models->push($item->resource);
        }

        $this->collection->first()?->prepare($models, $scope, $request);
    }

    /**
     * @return array<string, mixed>
     */
    private function render(IncludeScope $scope, Request $request): array
    {
        $data = [];
        foreach ($this->collection as $item) {
            $data[] = $item->withResourceKey($this->hasResourceKey ? $this->resourceKey : $item->resourceName())
                ->resolveIncluded($scope, $request)
                ->jsonSerialize();
        }

        return ['object' => 'list', 'data' => $data];
    }
}
