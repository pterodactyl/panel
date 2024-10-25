<?php

namespace Psalm\Issue;

class MixedArrayAccess extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 51;

    use MixedIssueTrait;
}
