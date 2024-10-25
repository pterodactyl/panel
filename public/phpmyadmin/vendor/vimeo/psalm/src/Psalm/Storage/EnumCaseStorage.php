<?php

namespace Psalm\Storage;

use Psalm\CodeLocation;

class EnumCaseStorage
{
    /**
     * @var int|string|null
     */
    public $value;

    /** @var CodeLocation */
    public $stmt_location;

    /**
     * @var bool
     */
    public $deprecated = false;

    /**
     * @param int|string|null  $value
     */
    public function __construct(
        $value,
        CodeLocation $location
    ) {
        $this->value = $value;
        $this->stmt_location = $location;
    }
}
