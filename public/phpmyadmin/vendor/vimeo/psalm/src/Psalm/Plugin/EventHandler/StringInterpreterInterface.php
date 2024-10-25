<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\StringInterpreterEvent;
use Psalm\Type\Atomic\TLiteralString;

interface StringInterpreterInterface
{
    /**
     * Called after a statement has been checked
     */
    public static function getTypeFromValue(StringInterpreterEvent $event): ?TLiteralString;
}
