<?php

namespace Psalm\Issue;

use Psalm\CodeLocation;

use function strtolower;

abstract class VariableIssue extends CodeIssue
{
    /**
     * @var string
     */
    public $var_name;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        string $var_name
    ) {
        parent::__construct($message, $code_location);
        $this->var_name = strtolower($var_name);
    }
}
