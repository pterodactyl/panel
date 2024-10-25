<?php

namespace Psalm\Issue;

class MixedArrayAssignment extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 117;

    use MixedIssueTrait;
}
