<?php

namespace Psalm\Issue;

use Psalm\CodeLocation;

abstract class ClassIssue extends CodeIssue
{
    /**
     * @var string
     */
    public $fq_classlike_name;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        string $fq_classlike_name
    ) {
        parent::__construct($message, $code_location);
        $this->fq_classlike_name = $fq_classlike_name;
    }
}
