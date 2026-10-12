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
 * A list of API objects, `{"object": "list", "data": [...]}`, as Fractal's collection
 * resource rendered it. Keys are dropped. At the root of a response a length-aware
 * paginator adds Fractal's `pagination` meta after any other meta.
 */
class ApiResourceCollection extends AnonymousResourceCollection implements IncludedResource
{
    use BuildsResponse;

    /** @var string|null */
    public static $wrap;

    /** @var class-string<ApiResource<*>> */
    public $collects;

    /**
     * Every item mapped into $collects, keyed as given.
     *
     * @var Collection<array-key, ApiResource<*>>
     */
    public $collection;

    /**
     * The paginator given, now holding the mapped items, or the mapped items themselves.
     *
     * @var Collection<array-key, ApiResource<*>>|AbstractPaginator<array-key, ApiResource<*>>
     */
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
     * Renders the list as the root of a response: the eager loads of its items and of the
     * requested includes are batched first when every item is a model.
     *
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
     * Renders the list as the root of a response for $includes, without the eager loading
     * resolve() batches first. Documentation examples render this way, as they did with
     * Fractal.
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
     * Fractal's pagination meta. The links are an empty object on a single page.
     *
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

        // Fractal's paginator adapter returned these through int return types, so a total
        // given as a string still rendered as a number.
        return [
            'total' => (int) $paginator->total(),
            'count' => count($paginator->items()),
            'per_page' => (int) $paginator->perPage(),
            'current_page' => (int) $currentPage,
            'total_pages' => (int) $lastPage,
            'links' => $links === [] ? new JsonEmptyObject : $links,
        ];
    }

    /**
     * Batches the eager loads of every item through the first one, as Fractal prepared a
     * collection only when all of its items were models.
     */
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
