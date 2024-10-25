<?php

namespace Psalm\Storage;

use Psalm\CodeLocation;

class AttributeStorage
{
    /**
     * @var string
     */
    public $fq_class_name;

    /**
     * @var list<AttributeArg>
     */
    public $args;

    /**
     * @var CodeLocation
     *
     * @psalm-suppress PossiblyUnusedProperty part of public API
     */
    public $location;

    /**
     * @var CodeLocation
     *
     * @psalm-suppress PossiblyUnusedProperty part of public API
     */
    public $name_location;

    /**
     * @param list<AttributeArg> $args
     */
    public function __construct(
        string $fq_class_name,
        array $args,
        CodeLocation $location,
        CodeLocation $name_location
    ) {
        $this->fq_class_name = $fq_class_name;
        $this->args = $args;
        $this->location = $location;
        $this->name_location = $name_location;
    }
}
