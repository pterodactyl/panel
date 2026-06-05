<?php

namespace Pterodactyl\Models\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class Identifiable
{
    public function __construct(public string $prefix, public string $column = 'uuid')
    {
    }
}
