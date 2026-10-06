<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Scribe\Attributes;

use Attribute;
use Knuckles\Scribe\Attributes\BodyParam;

#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class ExtensionFieldsParam extends BodyParam
{
    public function __construct()
    {
        parent::__construct(
            'extensions',
            'object',
            'Values for extension form fields, keyed by extension id and then by field.',
            required: false,
            example: ['billing' => ['plan' => 'gold']],
        );
    }
}
