<?php

namespace Psalm\Plugin\Hook;

use Psalm\Type\Atomic\TLiteralString;

/** @deprecated going to be removed in Psalm 5 */
interface StringInterpreterInterface
{
    /**
     * Called after a statement has been checked
     */
    public static function getTypeFromValue(
        string $value
    ): ?TLiteralString;
}
