<?php

namespace Psalm\Issue;

use Psalm\CodeLocation;

use function strtolower;

class MixedArgumentTypeCoercion extends ArgumentIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 194;

    use MixedIssueTrait;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        ?string $function_id = null,
        ?CodeLocation $origin_location = null
    ) {
        $this->code_location = $code_location;
        $this->message = $message;
        $this->function_id = $function_id ? strtolower($function_id) : null;
        $this->origin_location = $origin_location;
    }
}
