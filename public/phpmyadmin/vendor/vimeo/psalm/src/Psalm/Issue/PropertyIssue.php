<?php

namespace Psalm\Issue;

use Psalm\CodeLocation;

abstract class PropertyIssue extends CodeIssue
{
    /**
     * @var string
     */
    public $property_id;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        string $property_id
    ) {
        parent::__construct($message, $code_location);
        $this->property_id = $property_id;
    }
}
