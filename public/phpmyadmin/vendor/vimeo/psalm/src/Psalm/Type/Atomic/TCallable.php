<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Denotes the `callable` type. Can result from an `is_callable` check.
 */
class TCallable extends Atomic
{
    use CallableTrait;

    /**
     * @var string
     */
    public $value;

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): string {
        return 'callable';
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return $this->params === null && $this->return_type === null;
    }
}
