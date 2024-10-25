<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes an anonymous class (i.e. `new class{}`) with potential methods
 */
class TAnonymousClassInstance extends TNamedObject
{
    /**
     * @var string|null
     */
    public $extends;

    /**
     * @param string $value the name of the object
     */
    public function __construct(string $value, bool $was_static = false, ?string $extends = null)
    {
        parent::__construct($value, $was_static);

        $this->extends = $extends;
    }

    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): ?string {
        return $php_major_version > 7
            || ($php_major_version === 7 && $php_minor_version >= 2)
            ? ($this->extends ?? 'object') : null;
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     *
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        return $this->extends ?? 'object';
    }
}
