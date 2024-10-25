<?php

declare(strict_types=1);

namespace Psalm\Storage;

interface HasAttributesInterface
{
    /**
     * Returns a list of AttributeStorages with the same order they appear in the AttributeGroups they come from.
     *
     * @return list<AttributeStorage>
     *
     * @psalm-suppress PossiblyUnusedMethod part of public API
     */
    public function getAttributeStorages(): array;
}
