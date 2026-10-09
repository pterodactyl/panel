<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Scribe\Attributes;

use Attribute;
use Knuckles\Scribe\Attributes\BodyParam;

/** Documents the `extensions` input of an endpoint that creates or updates a resource extensions add fields to. */
#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class ExtensionFieldsParam extends BodyParam
{
    public function __construct()
    {
        parent::__construct(
            'extensions',
            'object',
            'Values for the fields extensions add to this resource, keyed by extension id and then by field name. Extensions left out keep their values.',
            required: false,
            example: ['billing' => ['plan' => 'pro']],
        );
    }
}
