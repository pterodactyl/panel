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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use LogicException;
use Pterodactyl\Data\ServerState;
use Pterodactyl\Http\Resources\Api\Concerns\BuildsResponse;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\Includes\IncludeScope;
use Pterodactyl\Http\Resources\Api\Includes\IncludeSet;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Support\JsonEmptyObject;
use UnexpectedValueException;

/**
 * The envelope is built here rather than by Laravel: resolve() never filters missing values
 * (which would turn arrays with numeric keys into lists) and never wraps the response in
 * `data`.
 *
 * @template TResource of Model|ServerState|array<mixed>
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
     * Returned whether or not the request asked for them, at every depth.
     *
     * @var list<string>
     */
    protected array $defaultIncludes = [];

    /** @var list<string> */
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

    /** @var array<string, mixed>|null */
    private ?array $included = null;

    /**
     * @param  TResource  $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    abstract public function resourceName(): ?string;

    public static function collection($resource): ApiResourceCollection
    {
        return new ApiResourceCollection($resource, static::class);
    }

    /**
     * Includes go in `relationships`: emitting it empty keeps its place, otherwise it is
     * appended last when something is included.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        throw new LogicException(sprintf('[%s] must define the attributes it renders.', static::class));
    }

    /**
     * Controllers ask for extension fields only on responses about one model, so a list never
     * runs every extension for every row. With `$read` false the attribute is present but
     * empty and no extension code runs.
     */
    public function withExtensionFields(bool $read = true): static
    {
        $this->extensionFields = true;
        $this->readExtensionFields = $read;

        return $this;
    }

    /**
     * @param  Request|null  $request
     * @return array<string, mixed>
     */
    final public function resolve($request = null): array
    {
        $request ??= $this->resolveRequestFromContainer();
        $includes = $this->includeSet($request);

        $model = $this->model();
        if ($model instanceof Model) {
            $this->prepare(new ModelCollection([$model]), $includes->root(), $request);
        }

        return $this->resolveFor($includes, $request);
    }

    /**
     * Without the eager loading resolve() batches first, as documentation examples render.
     *
     * @return array<string, mixed>
     */
    final public function resolveFor(IncludeSet $includes, Request $request): array
    {
        return $this->render($includes->root(), $request, $this->hasResourceKey ? $this->resourceKey : $this->resourceName());
    }

    final public function resolveIncluded(IncludeScope $scope, Request $request): static
    {
        $this->included = $this->render($scope, $request, $this->resourceKey);

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
     * Returning null leaves the include out; null() embeds the null resource.
     */
    protected function resolveInclude(string $include): ?IncludedResource
    {
        throw new LogicException(sprintf('[%s] does not define the [%s] include.', static::class, $include));
    }

    /**
     * Without a key the embedded object is named null.
     *
     * @template TData of Model|ServerState|array<mixed>
     *
     * @param  TData  $data
     * @param  class-string<ApiResource<TData>>|Closure(TData): array<string, mixed>  $resource
     * @return ApiResource<TData>
     */
    protected function item(Model|ServerState|array $data, string|Closure $resource, ?string $resourceKey = null): self
    {
        $item = $resource instanceof Closure ? new CallbackResource($data, $resource) : $this->makeResource($resource, $data);

        return $item->withResourceKey($resourceKey);
    }

    /**
     * @template TData of Model|ServerState|array<mixed>
     *
     * @param  array<TData>|Collection<int, TData>  $data
     * @param  class-string<ApiResource<TData>>|Closure(TData): array<string, mixed>  $resource
     */
    protected function items(array|Collection $data, string|Closure $resource, ?string $resourceKey = null): ApiResourceCollection
    {
        $items = [];
        foreach ($data as $value) {
            $items[] = $resource instanceof Closure ? new CallbackResource($value, $resource) : $this->makeResource($resource, $value);
        }

        return (new ApiResourceCollection($items, $resource instanceof Closure ? CallbackResource::class : $resource))->withResourceKey($resourceKey);
    }

    protected function null(): NullResource
    {
        return new NullResource;
    }

    /**
     * @template TNested of ApiResource<*>
     *
     * @param  class-string<TNested>  $resource
     * @param  Model|ServerState|array<mixed>  $data
     * @return TNested
     */
    protected function makeResource(string $resource, Model|ServerState|array $data): self
    {
        return new $resource($data);
    }

    protected function authorize(string $ability): bool
    {
        throw new LogicException(sprintf('[%s] does not gate includes by ability.', static::class));
    }

    protected function request(): Request
    {
        return $this->request ??= $this->resolveRequestFromContainer();
    }

    /**
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

    protected function exposesAllExtensionFields(): bool
    {
        return false;
    }

    protected function formatTimestamp(CarbonInterface|string|null $timestamp): string
    {
        throw_if($timestamp === null, UnexpectedValueException::class, 'Cannot format a missing model timestamp.');

        $value = $timestamp instanceof CarbonInterface
            ? CarbonImmutable::instance($timestamp)
            : CarbonImmutable::createFromFormat(CarbonInterface::DEFAULT_TO_STRING_FORMAT, $timestamp);

        throw_unless($value instanceof CarbonImmutable, UnexpectedValueException::class, "The model timestamp does not match Laravel's storage format.");

        return $value->setTimezone(self::RESPONSE_TIMEZONE)->toAtomString();
    }

    private function model(): ?Model
    {
        return $this->resource instanceof Model ? $this->resource : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function render(IncludeScope $scope, Request $request, ?string $resourceKey): array
    {
        $this->request = $request;

        $attributes = $this->toArray($request);

        $included = $this->includes($scope, $request);
        if ($included !== []) {
            throw_unless(($attributes['relationships'] ?? []) === [], LogicException::class, sprintf('[%s] may only emit an empty `relationships`; its includes fill it.', static::class));

            $attributes['relationships'] = $included;
        }

        // A falsy key ("" or "0") renders as null.
        $item = ['object' => $resourceKey === '' || $resourceKey === '0' ? null : $resourceKey, 'attributes' => $attributes];

        return $this->meta === [] ? $item : $item + ['meta' => $this->meta];
    }

    /**
     * Default includes first, then the requested ones, both in declared order rather than
     * request order.
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
