<?php

declare(strict_types=1);

namespace Pterodactyl\Models\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class Identifiable
{
    public function __construct(public string $prefix, public string $column = 'uuid') {}
}
