<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Scribe\Strategies;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Request as RequestFacade;
use InvalidArgumentException;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;
use Knuckles\Scribe\Attributes\ResponseFromTransformer as BaseResponseFromTransformer;
use Knuckles\Scribe\Extracting\DatabaseTransactionHelpers;
use Knuckles\Scribe\Extracting\Strategies\Responses\UseResponseAttributes;
use League\Fractal\Manager;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\Serializer;
use League\Fractal\TransformerAbstract;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludeSet;
use Pterodactyl\Models\User;
use Pterodactyl\Transformers\Api\Application\BaseTransformer;
use ReflectionClass;

class UsePterodactylResponseAttributes extends UseResponseAttributes
{
    use DatabaseTransactionHelpers;

    /**
     * Replaces the vendor TransformerResponseTools path, which crashes on
     * unpaginated collections and drops the resource key for them, so the
     * serialized "object" name would fall back to "resource".
     *
     * @return array{status: int, description: string|null, content: string|false}
     */
    protected function getTransformerResponse(BaseResponseFromTransformer $attributeInstance): array
    {
        // withCount is applied after instantiation: passing it through to Scribe
        // makes the factory helper spawn bare related factories, which fail on
        // models whose factories require sibling attributes.
        $withCount = $attributeInstance instanceof ResponseFromTransformer ? $attributeInstance->withCount : [];
        $transformerClass = $this->transformerClass($attributeInstance);
        $modelInstantiator = function () use ($attributeInstance, $withCount, $transformerClass) {
            $model = $this->instantiateExampleModel(
                $attributeInstance->model,
                $this->stringList($attributeInstance->factoryStates),
                $this->stringList($attributeInstance->with),
                (new ReflectionClass($transformerClass))->getMethod('transform'),
            );

            if ($withCount !== [] && $model instanceof Model && $model->exists) {
                $model->loadCount($withCount);
            }

            return $model;
        };

        $this->startDbTransaction();
        try {
            $this->authenticateGenerationRequest();
            $content = $this->fetchTransformerContent($attributeInstance, $modelInstantiator);
        } finally {
            $this->endDbTransaction();
        }

        return [
            'status' => $attributeInstance->status,
            'description' => $attributeInstance->description,
            'content' => $attributeInstance instanceof ResponseFromTransformer ? $this->withExampleMeta($content, $attributeInstance->meta) : $content,
        ];
    }

    /**
     * Renders #[ResponseFromResource] examples as getTransformerResponse() renders the
     * transformer ones: inside a transaction, for a root administrator, from a factory model
     * whose counts are loaded afterwards, with the attribute's object name and meta.
     *
     * @return array{status: int, description: string|null, content: string|false}
     */
    protected function getApiResourceResponse(ResponseFromApiResource $attributeInstance): array
    {
        throw_unless($attributeInstance instanceof ResponseFromResource, InvalidArgumentException::class, 'Document API resource responses with #['.ResponseFromResource::class.'].');

        $resourceClass = $this->resourceClass($attributeInstance);
        $modelInstantiator = function () use ($attributeInstance): Model {
            // withCount is applied after instantiation, as for transformers.
            $model = $this->instantiateExampleModel($attributeInstance->model, $this->stringList($attributeInstance->factoryStates), $this->stringList($attributeInstance->with));
            throw_unless($model instanceof Model, InvalidArgumentException::class, 'The model referenced by #[ResponseFromResource] must be an Eloquent model.');

            $counts = $this->stringList($attributeInstance->withCount);
            if ($counts !== [] && $model->exists) {
                $model->loadCount($counts);
            }

            return $model;
        };

        $this->startDbTransaction();
        try {
            $this->authenticateGenerationRequest();
            $content = $this->fetchResourceContent($attributeInstance, $resourceClass, $modelInstantiator);
        } finally {
            $this->endDbTransaction();
        }

        return [
            'status' => $attributeInstance->status,
            'description' => $attributeInstance->description,
            'content' => $this->withExampleMeta($content, $attributeInstance->meta),
        ];
    }

    /**
     * Transformers and resources render user-dependent fields (ownership flags, permission-gated
     * includes), so give the console request a root administrator for the duration
     * of the fetch. The user row lives inside the surrounding transaction.
     */
    private function authenticateGenerationRequest(): void
    {
        $user = User::factory()->create(['root_admin' => true]);

        resolve('request')->setUserResolver(fn () => $user);
    }

    private function fetchTransformerContent(BaseResponseFromTransformer $attribute, Closure $modelInstantiator): string
    {
        $fractal = new Manager;
        if ($serializer = $this->config->get('fractal.serializer')) {
            throw_unless(is_string($serializer), InvalidArgumentException::class, 'The configured Fractal serializer must be a class name.');

            $resolvedSerializer = resolve($serializer);
            throw_unless($resolvedSerializer instanceof Serializer, InvalidArgumentException::class, 'The configured Fractal serializer must implement the serializer contract.');

            $fractal->setSerializer($resolvedSerializer);
        }

        if ($attribute instanceof ResponseFromTransformer && $attribute->include !== []) {
            $fractal->parseIncludes($attribute->include);
        }

        $transformerClass = $this->transformerClass($attribute);
        $transformer = resolve($transformerClass);
        throw_unless($transformer instanceof TransformerAbstract, InvalidArgumentException::class, "Transformer [$transformerClass] must extend ".TransformerAbstract::class.'.');

        if (! $attribute->collection) {
            // Controllers add extension field values to responses about one resource. The
            // example shows the attribute without running installed extensions' code.
            if ($transformer instanceof BaseTransformer) {
                $transformer->withExtensionFields(read: false);
            }

            $resource = new Item($modelInstantiator(), $transformer, $attribute->resourceKey);

            return $fractal->createData($resource)->toJson();
        }

        $models = [$modelInstantiator()];
        $resource = new Collection($models, $transformer, $attribute->resourceKey);

        if ($attribute->paginate !== []) {
            $adapter = $attribute->paginate[0];
            $perPage = $attribute->paginate[1] ?? count($models);

            if (! is_string($adapter) || ! is_a($adapter, PaginatorInterface::class, true)) {
                throw new InvalidArgumentException(sprintf(
                    'The paginator adapter referenced by #[ResponseFromTransformer] must be a class-string implementing %s.',
                    PaginatorInterface::class,
                ));
            }

            throw_unless(is_int($perPage), InvalidArgumentException::class, 'The paginator page size must be an integer.');

            $paginator = new LengthAwarePaginator($models, count($models), $perPage);
            $resource->setPaginator(new $adapter($paginator));
        }

        return $fractal->createData($resource)->toJson();
    }

    /**
     * Rendered like the transformer examples: only the attribute's includes, as deep as
     * Fractal's default of ten levels, and none of the eager loading a controller's response
     * batches first.
     *
     * @param  class-string<ApiResource<*>>  $resourceClass
     * @param  Closure(): Model  $modelInstantiator
     */
    private function fetchResourceContent(ResponseFromResource $attribute, string $resourceClass, Closure $modelInstantiator): string
    {
        $includes = IncludeSet::parse($this->stringList($attribute->include));
        $request = RequestFacade::instance();

        if (! $attribute->collection) {
            // Controllers add extension field values to responses about one resource. The
            // example shows the attribute without running installed extensions' code.
            $resource = (new $resourceClass($modelInstantiator()))
                ->withExtensionFields(read: false)
                ->withResourceKey($attribute->resourceKey);

            return json_encode($resource->resolveFor($includes, $request), JSON_THROW_ON_ERROR);
        }

        $models = [$modelInstantiator()];
        $data = $attribute->paginate === null ? $models : new LengthAwarePaginator($models, count($models), $attribute->paginate);

        $collection = (new ApiResourceCollection($data, $resourceClass))->withResourceKey($attribute->resourceKey);

        return json_encode($collection->resolveFor($includes, $request), JSON_THROW_ON_ERROR);
    }

    /**
     * Merges an attribute's example meta into the rendered envelope's top-level meta, after
     * any pagination, mirroring what the controller adds and rendering alone cannot.
     *
     * @param  ApiPayload  $meta
     */
    private function withExampleMeta(string $content, array $meta): string|false
    {
        if ($meta === []) {
            return $content;
        }

        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $existingMeta = $decoded['meta'] ?? null;
            if (! is_array($existingMeta)) {
                $existingMeta = [];
            }

            $decoded['meta'] = array_merge($existingMeta, $meta);
            $content = json_encode($decoded);
        }

        return $content;
    }

    /**
     * Resolves and validates the API resource class referenced by a #[ResponseFromResource]
     * attribute.
     *
     * @return class-string<ApiResource<*>>
     */
    private function resourceClass(ResponseFromResource $attribute): string
    {
        if (! is_a($attribute->name, ApiResource::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'The class [%s] referenced by #[ResponseFromResource] must extend %s.',
                $attribute->name,
                ApiResource::class,
            ));
        }

        return $attribute->name;
    }

    /**
     * Resolves and validates the Fractal transformer class referenced by a
     * #[ResponseFromTransformer] attribute.
     *
     * @return class-string<TransformerAbstract>
     */
    private function transformerClass(BaseResponseFromTransformer $attribute): string
    {
        if (! is_a($attribute->name, TransformerAbstract::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'The class [%s] referenced by #[ResponseFromTransformer] must extend %s.',
                $attribute->name,
                TransformerAbstract::class,
            ));
        }

        return $attribute->name;
    }

    /**
     * @param  array<mixed>  $values
     * @return list<string>
     */
    private function stringList(array $values): array
    {
        $strings = [];
        foreach ($values as $value) {
            if (is_string($value)) {
                $strings[] = $value;
            }
        }

        return $strings;
    }
}
