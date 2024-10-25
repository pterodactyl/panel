<?php

namespace Psalm\Issue;

class MixedReturnStatement extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 138;

    use MixedIssueTrait;
}
