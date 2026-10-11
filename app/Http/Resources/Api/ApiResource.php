<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use LogicException;
use Pterodactyl\Data\ServerState;
use Pterodactyl\Http\Resources\Api\Concerns\BuildsResponse;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\Includes\IncludeScope;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Support\JsonEmptyObject;
use UnexpectedValueException;

/**
 * One API object, `{"object": name, "attributes": {...}}`, the shape Fractal rendered with
 * the PterodactylSerializer. A resource replaces a Fractal transformer: toArray() returns
 * what transform() returned, and includes, eager loading and extension fields behave as
 * they did, so every response stays byte for byte the same.
 *
 * The envelope is built here rather than by Laravel: resolve() never filters missing
 * values (which would turn arrays with numeric keys into lists), never wraps the response
 * in `data`, and the response is always 200 unless respond() asks for another status.
 *
 * @template TResource of ApiResourceData
 */
abstract class ApiResource extends JsonResource implements IncludedResource
{
    use BuildsResponse;

    public const string RESPONSE_TIMEZONE = 'UTC';

    /** @var string|null */
    public static $wrap;

    /** @var TResource */
    public $resource;

    /**
     * Includes returned whether or not the request asked for them, at every depth.
     *
     * @var list<string>
     */
    protected array $defaultIncludes = [];

    /**
     * Includes returned when the request asks for them.
     *
     * @var list<string>
     */
    protected array $availableIncludes = [];

    /** @var list<string> */
    protected array $eagerLoads = [];

    /** @var list<string> */
    protected array $eagerLoadCounts = [];

    /** @var array<string, string> relation alias => column */
    protected array $eagerLoadSums = [];

    /** @var array<string, array{relation: string, resource?: class-string<ApiResource<*>>, ability?: string}> */
    protected array $includeRelations = [];

    private ?Request $request = null;

    private bool $extensionFields = false;

    private bool $readExtensionFields = true;

    /** @var ApiResourceItem|null */
    private ?array $included = null;

    /**
     * @param  TResource  $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * The name of the object at the root of a response, unless the controller names it.
     */
    abstract public function resourceName(): ?string;

    /**
     * A list of these resources, `{"object": "list", "data": [...]}`.
     *
     * @param  array<array-key, TResource>|Collection<covariant array-key, covariant TResource>|AbstractPaginator<covariant array-key, covariant TResource>  $resource
     */
    public static function collection($resource): ApiResourceCollection
    {
        return new ApiResourceCollection($resource, static::class);
    }

    /**
     * The attributes of the resource; what a Fractal transformer's transform() returned. The
     * includes go in `relationships`: emitting it empty keeps its place, otherwise it is
     * appended last when something is included.
     *
     * @return ApiPayload
     */
    public function toArray(Request $request): array
    {
        throw new LogicException(sprintf('[%s] must define the attributes it renders.', static::class));
    }

    /**
     * Adds the values of extension fields to the resource as its `extensions` attribute.
     * Controllers ask for them on responses about one model, so a list never runs every
     * extension for every row. With `$read` false the attribute is present but empty and no
     * extension code runs, as in documentation examples.
     */
    public function withExtensionFields(bool $read = true): static
    {
        $this->extensionFields = true;
        $this->readExtensionFields = $read;

        return $this;
    }

    /**
     * Renders the resource as the root of a response: its eager loads and those of the
     * requested includes are batched first, then the object is built with its includes.
     *
     * @param  Request|null  $request
     * @return ApiResourceItem
     */
    final public function resolve($request = null): array
    {
        $request ??= $this->resolveRequestFromContainer();
        $scope = $this->rootScope($request);

        $model = $this->model();
        if ($model instanceof Model) {
            $this->prepare(new ModelCollection([$model]), $scope, $request);
        }

        return $this->render($scope, $request, $this->hasResourceKey ? $this->resourceKey : $this->resourceName());
    }

    final public function resolveIncluded(IncludeScope $scope, Request $request): static
    {
        $this->included = $this->render($scope, $request, $this->resourceKey);

        return $this;
    }

    /**
     * @return ApiResourceItem
     */
    final public function jsonSerialize(): array
    {
        return $this->included ?? $this->resolve($this->resolveRequestFromContainer());
    }

    /**
     * Batches what the resources of $models read before any of them renders: the declared
     * eager loads, counts and sums, and the relations of the includes this request returns,
     * through every level of includes. Includes still authorize when they render.
     *
     * @param  ModelCollection<int, Model>  $models
     */
    public function prepare(ModelCollection $models, IncludeScope $scope, Request $request): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $this->request = $request;

        $models->loadMissing($this->eagerLoads);
        foreach ($this->eagerLoadCounts as $relation) {
            $models->reject(fn (Model $model): bool => array_key_exists($relation.'_count', $model->getAttributes()))->loadCount($relation);
        }

        foreach ($this->eagerLoadSums as $relation => $column) {
            $alias = explode(' as ', $relation)[1];
            $models->reject(fn (Model $model): bool => array_key_exists($alias, $model->getAttributes()))->loadSum($relation, $column);
        }

        foreach ($this->includeRelations as $include => $definition) {
            if (! in_array($include, $this->defaultIncludes, true) && ! $scope->isRequested($include)) {
                continue;
            }

            if (isset($definition['ability']) && ! $this->authorize($definition['ability'])) {
                continue;
            }

            $models->loadMissing($definition['relation']);
            if (! isset($definition['resource'])) {
                continue;
            }

            // A relation value is a Model, a Collection of Models, or null; flattening one
            // level and keeping only Models covers all three without a runtime type check.
            $related = new ModelCollection(
                $models->pluck($definition['relation'])->flatten(1)->whereInstanceOf(Model::class)->values()->all(),
            );

            $first = $related->first();
            if ($first !== null) {
                $this->makeResource($definition['resource'], $first)->prepare($related, $scope->child($include), $request);
            }
        }
    }

    /**
     * What $include embeds: a resource from item() or items(), null() for the null resource,
     * or null to leave the include out. A resource with includes matches each name to the
     * method that builds it.
     */
    protected function resolveInclude(string $include): ?IncludedResource
    {
        throw new LogicException(sprintf('[%s] does not define the [%s] include.', static::class, $include));
    }

    /**
     * Embeds one resource: $resource is a resource class, or a closure that returns the
     * attributes. Without a key the embedded object is named null, as with Fractal's item().
     *
     * @template TData of ApiResourceData
     *
     * @param  TData  $data
     * @param  class-string<ApiResource<TData>>|Closure(TData): ApiPayload  $resource
     * @return ApiResource<TData>
     */
    protected function item(Model|ServerState|array $data, string|Closure $resource, ?string $resourceKey = null): self
    {
        $item = $resource instanceof Closure ? new CallbackResource($data, $resource) : $this->makeResource($resource, $data);

        return $item->withResourceKey($resourceKey);
    }

    /**
     * Embeds a list of resources, as with Fractal's collection().
     *
     * @template TData of ApiResourceData
     *
     * @param  array<array-key, TData>|Collection<covariant array-key, covariant TData>  $data
     * @param  class-string<ApiResource<TData>>|Closure(TData): ApiPayload  $resource
     */
    protected function items(array|Collection $data, string|Closure $resource, ?string $resourceKey = null): ApiResourceCollection
    {
        $items = [];
        foreach ($data as $value) {
            $items[] = $resource instanceof Closure ? new CallbackResource($value, $resource) : $this->makeResource($resource, $value);
        }

        return (new ApiResourceCollection($items, $resource instanceof Closure ? CallbackResource::class : $resource))->withResourceKey($resourceKey);
    }

    /**
     * Embeds `{"object": "null_resource", "attributes": null}`.
     */
    protected function null(): NullResource
    {
        return new NullResource;
    }

    /**
     * Builds a nested resource. Each API's base resource only nests resources of its own API.
     *
     * @template TNested of ApiResource<*>
     *
     * @param  class-string<TNested>  $resource
     * @param  ApiResourceData  $data
     * @return TNested
     */
    protected function makeResource(string $resource, Model|ServerState|array $data): self
    {
        return new $resource($data);
    }

    /**
     * Whether the requester may read what an include gated by $ability returns.
     */
    protected function authorize(string $ability): bool
    {
        throw new LogicException(sprintf('[%s] does not gate includes by ability.', static::class));
    }

    /**
     * The request this resource renders for.
     */
    protected function request(): Request
    {
        return $this->request ??= $this->resolveRequestFromContainer();
    }

    /**
     * The `extensions` attribute of a model, keyed by extension id, when the controller asked
     * for extension fields; empty otherwise, so the attribute is left out.
     *
     * @return array{extensions?: array<string, ExtensionFieldValues|JsonEmptyObject>|JsonEmptyObject}
     */
    protected function extensionFields(Model $model): array
    {
        if (! $this->extensionFields) {
            return [];
        }

        if (! $this->readExtensionFields) {
            return ['extensions' => new JsonEmptyObject];
        }

        $values = array_map(
            fn (array $values): array|JsonEmptyObject => $values === [] ? new JsonEmptyObject : $values,
            App::make(ExtensionFields::class)->values($model, applicationApi: ! $this->exposesAllExtensionFields()),
        );

        return ['extensions' => $values === [] ? new JsonEmptyObject : $values];
    }

    /**
     * Whether this API returns the values of every extension's fields, rather than only
     * those marked #[ApplicationApi].
     */
    protected function exposesAllExtensionFields(): bool
    {
        return false;
    }

    /**
     * Return an ISO-8601 formatted timestamp to use in the API response.
     */
    protected function formatTimestamp(CarbonInterface|string|null $timestamp): string
    {
        throw_if($timestamp === null, UnexpectedValueException::class, 'Cannot format a missing model timestamp.');

        $value = $timestamp instanceof CarbonInterface
            ? CarbonImmutable::instance($timestamp)
            : CarbonImmutable::createFromFormat(CarbonInterface::DEFAULT_TO_STRING_FORMAT, $timestamp);

        throw_unless($value instanceof CarbonImmutable, UnexpectedValueException::class, "The model timestamp does not match Laravel's storage format.");

        return $value->setTimezone(self::RESPONSE_TIMEZONE)->toAtomString();
    }

    /**
     * The model this resource renders, when it renders one.
     */
    private function model(): ?Model
    {
        return $this->resource instanceof Model ? $this->resource : null;
    }

    /**
     * The object for this resource under $scope: its attributes with the includes the
     * request asked for merged into `relationships`, then any meta.
     *
     * @return ApiResourceItem
     */
    private function render(IncludeScope $scope, Request $request, ?string $resourceKey): array
    {
        $this->request = $request;

        $attributes = $this->toArray($request);

        $included = $this->includes($scope, $request);
        if ($included !== []) {
            // Fractal merged the includes into the `relationships` a transformer emitted, which
            // was always empty: assigning keeps its place, or appends it when it was not emitted.
            throw_unless(($attributes['relationships'] ?? []) === [], LogicException::class, sprintf('[%s] may only emit an empty `relationships`; its includes fill it.', static::class));

            $attributes['relationships'] = $included;
        }

        // Fractal names an object only after a non-empty key, so "" and "0" render null.
        $item = ['object' => $resourceKey === '' || $resourceKey === '0' ? null : $resourceKey, 'attributes' => $attributes];

        return $this->meta === [] ? $item : $item + ['meta' => $this->meta];
    }

    /**
     * The default includes in their declared order, then the requested ones in the order
     * they are declared, each resolved under its own scope. An include that returns null is
     * left out.
     *
     * @return array<string, IncludedResource>
     */
    private function includes(IncludeScope $scope, Request $request): array
    {
        $includes = $this->defaultIncludes;
        foreach ($this->availableIncludes as $include) {
            if ($scope->isRequested($include)) {
                $includes[] = $include;
            }
        }

        $included = [];
        foreach ($includes as $include) {
            $resource = $this->resolveInclude($include);
            if ($resource instanceof IncludedResource) {
                $included[$include] = $resource->resolveIncluded($scope->child($include), $request);
            }
        }

        return $included;
    }
}
