<?php

namespace Psalm\Issue;

use Psalm\CodeLocation;

interface MixedIssue
{
    public function getMixedOriginMessage(): string;

    public function getOriginalLocation(): ?CodeLocation;
}
