<?php

namespace Psalm\Progress;

class VoidProgress extends Progress
{
    public function write(string $message): void
    {
    }
}
