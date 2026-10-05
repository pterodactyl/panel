<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Scribe\Attributes;

use Attribute;
use Knuckles\Scribe\Attributes\ResponseField as BaseResponseField;

/**
 * Declares schema metadata for one field of a transformer's output, placed on
 * the transformer class itself so the OpenAPI schema stays next to the code
 * that produces the field. Field names are relative to the attributes object
 * and may use dot paths (e.g. "feature_limits.databases").
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class ResponseField extends BaseResponseField
{
    /**
     * @param  OpenApiSchema  $schema  Raw OpenAPI schema fragment replacing the inferred
     *                                 one - for shapes the simple parameters cannot
     *                                 express (additionalProperties maps, oneOf unions).
     */
    public function __construct(
        string $name,
        ?string $type = null,
        ?string $description = '',
        ?bool $required = true,
        mixed $example = null,
        mixed $enum = null,
        ?bool $nullable = false,
        ?bool $deprecated = false,
        public array $schema = [],
    ) {
        parent::__construct($name, $type, $description, $required, $example, $enum, $nullable, $deprecated);
    }
}
