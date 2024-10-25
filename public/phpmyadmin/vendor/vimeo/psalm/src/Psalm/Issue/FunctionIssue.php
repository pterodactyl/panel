<?php

namespace Psalm\Issue;

use Psalm\CodeLocation;

use function strtolower;

abstract class FunctionIssue extends CodeIssue
{
    /**
     * @var string
     */
    public $function_id;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        string $function_id
    ) {
        parent::__construct($message, $code_location);
        $this->function_id = strtolower($function_id);
    }
}
