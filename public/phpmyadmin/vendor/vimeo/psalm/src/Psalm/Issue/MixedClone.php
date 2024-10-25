<?php

namespace Psalm\Issue;

class MixedClone extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 227;

    use MixedIssueTrait;
}
