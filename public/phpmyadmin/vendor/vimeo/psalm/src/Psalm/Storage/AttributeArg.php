<?php

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Type\Union;

class AttributeArg
{
    /**
     * @var ?string
     * @psalm-suppress PossiblyUnusedProperty It's part of the public API for now
     */
    public $name;

    /**
     * @var Union|UnresolvedConstantComponent
     */
    public $type;

    /**
     * @var CodeLocation
     * @psalm-suppress PossiblyUnusedProperty It's part of the public API for now
     */
    public $location;

    /**
     * @param Union|UnresolvedConstantComponent $type
     */
    public function __construct(
        ?string $name,
        $type,
        CodeLocation $location
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->location = $location;
    }
}
