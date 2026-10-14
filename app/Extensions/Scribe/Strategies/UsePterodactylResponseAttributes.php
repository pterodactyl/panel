<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Scribe\Strategies;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Request as RequestFacade;
use InvalidArgumentException;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;
use Knuckles\Scribe\Extracting\DatabaseTransactionHelpers;
use Knuckles\Scribe\Extracting\Strategies\Responses\UseResponseAttributes;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludeSet;
use Pterodactyl\Models\User;

class UsePterodactylResponseAttributes extends UseResponseAttributes
{
    use DatabaseTransactionHelpers;

    /**
     * Renders #[ResponseFromResource] examples inside a transaction, for a root administrator,
     * from a factory model whose counts are loaded afterwards, with the attribute's object
     * name and meta.
     *
     * @return array{status: int, description: string|null, content: string|false}
     */
    protected function getApiResourceResponse(ResponseFromApiResource $attributeInstance): array
    {
        throw_unless($attributeInstance instanceof ResponseFromResource, InvalidArgumentException::class, 'Document API resource responses with #['.ResponseFromResource::class.'].');

        $resourceClass = $this->resourceClass($attributeInstance);
        $modelInstantiator = function () use ($attributeInstance): Model {
            // Counts are loaded after instantiation: passing withCount through to Scribe
            // makes the factory helper spawn bare related factories, which fail on models
            // whose factories require sibling attributes.
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
     * Resources render user-dependent fields (ownership flags, permission-gated includes), so
     * give the console request a root administrator for the duration of the fetch. The user
     * row lives inside the surrounding transaction.
     */
    private function authenticateGenerationRequest(): void
    {
        $user = User::factory()->create(['root_admin' => true]);

        resolve('request')->setUserResolver(fn () => $user);
    }

    /**
     * Only the attribute's includes, up to ten levels deep, and none of the eager loading a
     * controller's response batches first.
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
     * @param  array<string, mixed>  $meta
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
