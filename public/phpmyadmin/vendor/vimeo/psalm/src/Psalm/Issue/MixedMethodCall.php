<?php

namespace Psalm\Issue;

class MixedMethodCall extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 15;

    use MixedIssueTrait;
}
