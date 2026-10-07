<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Scribe\Strategies;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Knuckles\Scribe\Attributes\ResponseFromTransformer as BaseResponseFromTransformer;
use Knuckles\Scribe\Extracting\DatabaseTransactionHelpers;
use Knuckles\Scribe\Extracting\Strategies\Responses\UseResponseAttributes;
use League\Fractal\Manager;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\Serializer;
use League\Fractal\TransformerAbstract;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Models\User;
use Pterodactyl\Support\JsonValueGuard;
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
            $factoryStates = $attributeInstance->factoryStates;
            $relations = $attributeInstance->with;
            JsonValueGuard::assertValue($factoryStates);
            JsonValueGuard::assertValue($relations);
            $model = $this->instantiateExampleModel(
                $attributeInstance->model,
                $this->stringList($factoryStates),
                $this->stringList($relations),
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

        if ($attributeInstance instanceof ResponseFromTransformer && $attributeInstance->meta !== []) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $existingMeta = $decoded['meta'] ?? null;
                if (! is_array($existingMeta)) {
                    $existingMeta = [];
                }

                $decoded['meta'] = array_merge($existingMeta, $attributeInstance->meta);
                $content = json_encode($decoded);
            }
        }

        return [
            'status' => $attributeInstance->status,
            'description' => $attributeInstance->description,
            'content' => $content,
        ];
    }

    /**
     * Transformers render user-dependent fields (ownership flags, permission-gated
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
            // Controllers add extension field values to responses about one resource.
            if ($transformer instanceof BaseTransformer) {
                $transformer->withExtensionFields();
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
     * @param  array<array-key, ApiValue9>  $values
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
