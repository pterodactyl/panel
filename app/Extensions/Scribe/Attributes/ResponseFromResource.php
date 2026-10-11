<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Scribe\Attributes;

use Attribute;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

/**
 * Documents a response rendered by one of our API resources, the way #[ResponseFromTransformer]
 * documents a Fractal one: the example is rendered from a factory model with the given
 * includes, object name and meta.
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class ResponseFromResource extends ResponseFromApiResource
{
    /**
     * @param  list<string>  $factoryStates  Factory states applied to the example model.
     * @param  list<string>  $with  Relationships eager loaded onto the example model.
     * @param  string|null  $resourceKey  The `object` of the rendered items; null renders null.
     * @param  int|null  $paginate  Renders a list paginated by this many per page.
     * @param  array<string, mixed>  $meta  Example values merged into the response envelope's
     *                                      top-level meta key, mirroring the controller's
     *                                      withMeta() output that rendering the resource alone
     *                                      cannot reproduce.
     * @param  string[]  $include  Includes to render so the documented response carries
     *                             the relationships the endpoint can embed.
     * @param  string[]  $withCount  Relationship counts to load on the example model,
     *                               mirroring the controller's withCount() query.
     */
    public function __construct(
        string $name,
        ?string $model = null,
        int $status = 200,
        ?string $description = '',
        bool $collection = false,
        array $factoryStates = [],
        array $with = [],
        public ?string $resourceKey = null,
        ?int $paginate = null,
        public array $meta = [],
        public array $include = [],
        array $withCount = [],
    ) {
        parent::__construct($name, $model, $status, $description, $collection, $factoryStates, $with, $paginate, withCount: $withCount);
    }
}
