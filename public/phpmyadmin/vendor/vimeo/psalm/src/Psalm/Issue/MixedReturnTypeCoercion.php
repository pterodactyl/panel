<?php

namespace Psalm\Issue;

class MixedReturnTypeCoercion extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 197;

    use MixedIssueTrait;
}
