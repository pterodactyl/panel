<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Scribe\Attributes;

use Attribute;
use Knuckles\Scribe\Attributes\ResponseFromTransformer as BaseResponseFromTransformer;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class ResponseFromTransformer extends BaseResponseFromTransformer
{
    /**
     * @param  list<string>  $factoryStates  Factory states applied to the example model.
     * @param  list<string>  $with  Relationships eager loaded onto the example model.
     * @param  array{}|array{class-string, int}  $paginate  Format: [adapter, numberPerPage].
     * @param  ApiPayload  $meta  Example values merged into the response envelope's
     *                            top-level meta key, mirroring the controller's
     *                            Fractal ->addMeta() output that transformer
     *                            execution alone cannot reproduce.
     * @param  string[]  $include  Fractal includes to execute so the documented response
     *                             carries the relationships the endpoint can embed.
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
        ?string $resourceKey = null,
        array $paginate = [],
        public array $meta = [],
        public array $include = [],
        public array $withCount = [],
    ) {
        parent::__construct($name, $model, $status, $description, $collection, $factoryStates, $with, $resourceKey, $paginate);
    }
}
