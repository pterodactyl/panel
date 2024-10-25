<?php

namespace Psalm\Issue;

class MixedPropertyFetch extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 34;

    use MixedIssueTrait;
}
