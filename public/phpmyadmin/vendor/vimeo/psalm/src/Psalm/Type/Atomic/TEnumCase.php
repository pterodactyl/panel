<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes an enum with a specific value
 */
class TEnumCase extends TNamedObject
{
    /**
     * @var string
     */
    public $case_name;

    public function __construct(string $fq_enum_name, string $case_name)
    {
        parent::__construct($fq_enum_name);

        $this->case_name = $case_name;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'enum(' . $this->value . '::' . $this->case_name . ')';
    }

    public function getId(bool $nested = false): string
    {
        return 'enum(' . $this->value . '::' . $this->case_name . ')';
    }

    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): ?string {
        return $this->value;
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
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
        return $this->value . '::' . $this->case_name;
    }
}
