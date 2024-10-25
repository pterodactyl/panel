<?php

namespace Psalm\Issue;

use Psalm\CodeLocation;

class DocblockTypeContradiction extends CodeIssue
{
    public const ERROR_LEVEL = 2;
    public const SHORTCODE = 155;

    public function __construct(string $message, CodeLocation $code_location, ?string $dupe_key)
    {
        parent::__construct($message, $code_location);
        $this->dupe_key = $dupe_key;
    }
}
