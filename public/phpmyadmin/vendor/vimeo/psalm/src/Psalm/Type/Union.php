<?php

namespace Psalm\Type;

use InvalidArgumentException;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Internal\TypeVisitor\CanContainObjectTypeVisitor;
use Psalm\Internal\TypeVisitor\ContainsClassLikeVisitor;
use Psalm\Internal\TypeVisitor\ContainsLiteralVisitor;
use Psalm\Internal\TypeVisitor\FromDocblockSetter;
use Psalm\Internal\TypeVisitor\TemplateTypeCollector;
use Psalm\Internal\TypeVisitor\TypeChecker;
use Psalm\Internal\TypeVisitor\TypeScanner;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;
use Psalm\Type;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyLowercaseString;
use Psalm\Type\Atomic\TNonspecificLiteralInt;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TPositiveInt;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTrue;
use UnexpectedValueException;

use function array_filter;
use function array_merge;
use function array_unique;
use function count;
use function get_class;
use function implode;
use function ksort;
use function reset;
use function sort;
use function strpos;

class Union implements TypeNode
{
    /**
     * @var non-empty-array<string, Atomic>
     */
    private $types;

    /**
     * Whether the type originated in a docblock
     *
     * @var bool
     */
    public $from_docblock = false;

    /**
     * Whether the type originated from integer calculation
     *
     * @var bool
     */
    public $from_calculation = false;

    /**
     * Whether the type originated from a property
     *
     * This helps turn isset($foo->bar) into a different sort of issue
     *
     * @var bool
     */
    public $from_property = false;

    /**
     * Whether the type originated from *static* property
     *
     * Unlike non-static properties, static properties have no prescribed place
     * like __construct() to be initialized in
     *
     * @var bool
     */
    public $from_static_property = false;

    /**
     * Whether the property that this type has been derived from has been initialized in a constructor
     *
     * @var bool
     */
    public $initialized = true;

    /**
     * Which class the type was initialised in
     *
     * @var ?string
     */
    public $initialized_class;

    /**
     * Whether or not the type has been checked yet
     *
     * @var bool
     */
    public $checked = false;

    /**
     * @var bool
     */
    public $failed_reconciliation = false;

    /**
     * Whether or not to ignore issues with possibly-null values
     *
     * @var bool
     */
    public $ignore_nullable_issues = false;

    /**
     * Whether or not to ignore issues with possibly-false values
     *
     * @var bool
     */
    public $ignore_falsable_issues = false;

    /**
     * Whether or not to ignore issues with isset on this type
     *
     * @var bool
     */
    public $ignore_isset = false;

    /**
     * Whether or not this variable is possibly undefined
     *
     * @var bool
     */
    public $possibly_undefined = false;

    /**
     * Whether or not this variable is possibly undefined
     *
     * @var bool
     */
    public $possibly_undefined_from_try = false;

    /**
     * Whether or not this union had a template, since replaced
     *
     * @var bool
     */
    public $had_template = false;

    /**
     * Whether or not this union comes from a template "as" default
     *
     * @var bool
     */
    public $from_template_default = false;

    /**
     * @var array<string, TLiteralString>
     */
    private $literal_string_types = [];

    /**
     * @var array<string, TClassString>
     */
    private $typed_class_strings = [];

    /**
     * @var array<string, TLiteralInt>
     */
    private $literal_int_types = [];

    /**
     * @var array<string, TLiteralFloat>
     */
    private $literal_float_types = [];

    /**
     * Whether or not the type was passed by reference
     *
     * @var bool
     */
    public $by_ref = false;

    /**
     * @var bool
     */
    public $reference_free = false;

    /**
     * @var bool
     */
    public $allow_mutations = true;

    /**
     * @var bool
     */
    public $has_mutations = true;

    /** @var null|string */
    private $id;

    /**
     * @var array<string, DataFlowNode>
     */
    public $parent_nodes = [];

    /**
     * @var bool
     */
    public $different = false;

    /**
     * Constructs an Union instance
     *
     * @param non-empty-array<int, Atomic>     $types
     */
    public function __construct(array $types)
    {
        $from_docblock = false;

        $keyed_types = [];

        foreach ($types as $type) {
            $key = $type->getKey();
            $keyed_types[$key] = $type;

            if ($type instanceof TLiteralInt) {
                $this->literal_int_types[$key] = $type;
            } elseif ($type instanceof TLiteralString) {
                $this->literal_string_types[$key] = $type;
            } elseif ($type instanceof TLiteralFloat) {
                $this->literal_float_types[$key] = $type;
            } elseif ($type instanceof TClassString
                && ($type->as_type || $type instanceof TTemplateParamClass)
            ) {
                $this->typed_class_strings[$key] = $type;
            }

            $from_docblock = $from_docblock || $type->from_docblock;
        }

        $this->types = $keyed_types;

        $this->from_docblock = $from_docblock;
    }

    /**
     * @param non-empty-array<string, Atomic>  $types
     */
    public function replaceTypes(array $types): void
    {
        $this->types = $types;
    }

    /**
     * @psalm-mutation-free
     * @return non-empty-array<string, Atomic>
     */
    public function getAtomicTypes(): array
    {
        return $this->types;
    }

    public function addType(Atomic $type): void
    {
        $this->types[$type->getKey()] = $type;

        if ($type instanceof TLiteralString) {
            $this->literal_string_types[$type->getKey()] = $type;
        } elseif ($type instanceof TLiteralInt) {
            $this->literal_int_types[$type->getKey()] = $type;
        } elseif ($type instanceof TLiteralFloat) {
            $this->literal_float_types[$type->getKey()] = $type;
        } elseif ($type instanceof TString && $this->literal_string_types) {
            foreach ($this->literal_string_types as $key => $_) {
                unset($this->literal_string_types[$key], $this->types[$key]);
            }
            if (!$type instanceof TClassString
                || (!$type->as_type && !$type instanceof TTemplateParamClass)
            ) {
                foreach ($this->typed_class_strings as $key => $_) {
                    unset($this->typed_class_strings[$key], $this->types[$key]);
                }
            }
        } elseif ($type instanceof TInt && $this->literal_int_types) {
            //we remove any literal that is already included in a wider type
            $int_type_in_range = TIntRange::convertToIntRange($type);
            foreach ($this->literal_int_types as $key => $literal_int_type) {
                if ($int_type_in_range->contains($literal_int_type->value)) {
                    unset($this->literal_int_types[$key], $this->types[$key]);
                }
            }
        } elseif ($type instanceof TFloat && $this->literal_float_types) {
            foreach ($this->literal_float_types as $key => $_) {
                unset($this->literal_float_types[$key], $this->types[$key]);
            }
        }

        $this->id = null;
    }

    public function __clone()
    {
        $this->literal_string_types = [];
        $this->literal_int_types = [];
        $this->literal_float_types = [];
        $this->typed_class_strings = [];

        foreach ($this->types as $key => &$type) {
            $type = clone $type;

            if ($type instanceof TLiteralInt) {
                $this->literal_int_types[$key] = $type;
            } elseif ($type instanceof TLiteralString) {
                $this->literal_string_types[$key] = $type;
            } elseif ($type instanceof TLiteralFloat) {
                $this->literal_float_types[$key] = $type;
            } elseif ($type instanceof TClassString
                && ($type->as_type || $type instanceof TTemplateParamClass)
            ) {
                $this->typed_class_strings[$key] = $type;
            }
        }
    }

    public function __toString(): string
    {
        $types = [];

        $printed_int = false;
        $printed_float = false;
        $printed_string = false;

        foreach ($this->types as $type) {
            if ($type instanceof TLiteralFloat) {
                if ($printed_float) {
                    continue;
                }

                $printed_float = true;
            } elseif ($type instanceof TLiteralString) {
                if ($printed_string) {
                    continue;
                }

                $printed_string = true;
            } elseif ($type instanceof TLiteralInt) {
                if ($printed_int) {
                    continue;
                }

                $printed_int = true;
            }

            $types[] = (string)$type;
        }

        sort($types);
        return implode('|', $types);
    }

    public function getKey(): string
    {
        $types = [];

        $printed_int = false;
        $printed_float = false;
        $printed_string = false;

        foreach ($this->types as $type) {
            if ($type instanceof TLiteralFloat) {
                if ($printed_float) {
                    continue;
                }

                $types[] = 'float';
                $printed_float = true;
            } elseif ($type instanceof TLiteralString) {
                if ($printed_string) {
                    continue;
                }

                $types[] = 'string';
                $printed_string = true;
            } elseif ($type instanceof TLiteralInt) {
                if ($printed_int) {
                    continue;
                }

                $types[] = 'int';
                $printed_int = true;
            } else {
                $types[] = $type->getKey();
            }
        }

        sort($types);
        return implode('|', $types);
    }

    public function getId(): string
    {
        if ($this->id) {
            return $this->id;
        }

        $types = [];
        foreach ($this->types as $type) {
            $types[] = $type->getId();
        }
        sort($types);

        if (count($types) > 1) {
            foreach ($types as $i => $type) {
                if (strpos($type, ' as ') && strpos($type, '(') === false) {
                    $types[$i] = '(' . $type . ')';
                }
            }
        }

        $id = implode('|', $types);

        $this->id = $id;

        return $id;
    }

    public function getAssertionString(bool $exact = false): string
    {
        $assertions = [];
        foreach ($this->types as $type) {
            $assertions[] = $type->getAssertionString($exact);
        }

        $assertions = array_unique($assertions);
        if (count($assertions) !== 1) {
            throw new UnexpectedValueException('Should only be one type per assertion');
        }

        return reset($assertions);
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
        $other_types = [];

        $literal_ints = [];
        $literal_strings = [];

        $has_non_literal_int = false;
        $has_non_literal_string = false;

        foreach ($this->types as $type) {
            $type_string = $type->toNamespacedString($namespace, $aliased_classes, $this_class, $use_phpdoc_format);
            if ($type instanceof TLiteralInt) {
                $literal_ints[] = $type_string;
            } elseif ($type instanceof TLiteralString) {
                $literal_strings[] = $type_string;
            } else {
                if (get_class($type) === TString::class) {
                    $has_non_literal_string = true;
                } elseif (get_class($type) === TInt::class) {
                    $has_non_literal_int = true;
                }
                $other_types[] = $type_string;
            }
        }

        if (count($literal_ints) <= 3 && !$has_non_literal_int) {
            $other_types = array_merge($other_types, $literal_ints);
        } else {
            $other_types[] = 'int';
        }

        if (count($literal_strings) <= 3 && !$has_non_literal_string) {
            $other_types = array_merge($other_types, $literal_strings);
        } else {
            $other_types[] = 'string';
        }

        sort($other_types);
        return implode('|', array_unique($other_types));
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): ?string {
        if (!$this->isSingleAndMaybeNullable()) {
            if ($php_major_version < 8) {
                return null;
            }
        } elseif ($php_major_version < 7
            || (isset($this->types['null']) && $php_major_version === 7 && $php_minor_version < 1)
        ) {
            return null;
        }

        $types = $this->types;

        $nullable = false;

        if (isset($types['null']) && count($types) > 1) {
            unset($types['null']);

            $nullable = true;
        }

        $falsable = false;

        if (isset($types['false']) && count($types) > 1) {
            unset($types['false']);

            $falsable = true;
        }

        $php_types = [];

        foreach ($types as $atomic_type) {
            $php_type = $atomic_type->toPhpString(
                $namespace,
                $aliased_classes,
                $this_class,
                $php_major_version,
                $php_minor_version
            );

            if (!$php_type) {
                return null;
            }

            $php_types[] = $php_type;
        }

        if ($falsable) {
            if ($nullable) {
                $php_types['null'] = 'null';
            }
            $php_types['false'] = 'false';
            ksort($php_types);
            return implode('|', array_unique($php_types));
        }

        if ($php_major_version < 8) {
            return ($nullable ? '?' : '') . implode('|', array_unique($php_types));
        }
        if ($nullable) {
            $php_types['null'] = 'null';
        }
        return implode('|', array_unique($php_types));
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        if (!$this->isSingleAndMaybeNullable() && $php_major_version < 8) {
            return false;
        }

        $types = $this->types;

        if (isset($types['null'])) {
            if (count($types) > 1) {
                unset($types['null']);
            } else {
                return false;
            }
        }

        return !array_filter(
            $types,
            function ($atomic_type) use ($php_major_version, $php_minor_version) {
                return !$atomic_type->canBeFullyExpressedInPhp($php_major_version, $php_minor_version);
            }
        );
    }

    public function removeType(string $type_string): bool
    {
        if (isset($this->types[$type_string])) {
            unset($this->types[$type_string]);

            if (strpos($type_string, '(')) {
                unset(
                    $this->literal_string_types[$type_string],
                    $this->literal_int_types[$type_string],
                    $this->literal_float_types[$type_string]
                );
            }

            $this->id = null;

            return true;
        }

        if ($type_string === 'string') {
            if ($this->literal_string_types) {
                foreach ($this->literal_string_types as $literal_key => $_) {
                    unset($this->types[$literal_key]);
                }
                $this->literal_string_types = [];
            }

            if ($this->typed_class_strings) {
                foreach ($this->typed_class_strings as $typed_class_key => $_) {
                    unset($this->types[$typed_class_key]);
                }
                $this->typed_class_strings = [];
            }

            unset($this->types['class-string'], $this->types['trait-string']);
        } elseif ($type_string === 'int' && $this->literal_int_types) {
            foreach ($this->literal_int_types as $literal_key => $_) {
                unset($this->types[$literal_key]);
            }
            $this->literal_int_types = [];
        } elseif ($type_string === 'float' && $this->literal_float_types) {
            foreach ($this->literal_float_types as $literal_key => $_) {
                unset($this->types[$literal_key]);
            }
            $this->literal_float_types = [];
        }

        return false;
    }

    public function bustCache(): void
    {
        $this->id = null;
    }

    public function hasType(string $type_string): bool
    {
        return isset($this->types[$type_string]);
    }

    public function hasArray(): bool
    {
        return isset($this->types['array']);
    }

    public function hasIterable(): bool
    {
        return isset($this->types['iterable']);
    }

    public function hasList(): bool
    {
        return isset($this->types['array']) && $this->types['array'] instanceof TList;
    }

    public function hasClassStringMap(): bool
    {
        return isset($this->types['array']) && $this->types['array'] instanceof TClassStringMap;
    }

    public function isTemplatedClassString(): bool
    {
        return $this->isSingle()
            && count(
                array_filter(
                    $this->types,
                    function ($type): bool {
                        return $type instanceof TTemplateParamClass;
                    }
                )
            ) === 1;
    }

    public function hasArrayAccessInterface(Codebase $codebase): bool
    {
        return (bool)array_filter(
            $this->types,
            function ($type) use ($codebase) {
                return $type->hasArrayAccessInterface($codebase);
            }
        );
    }

    public function hasCallableType(): bool
    {
        return $this->getCallableTypes() || $this->getClosureTypes();
    }

    /**
     * @return array<string, TCallable>
     */
    public function getCallableTypes(): array
    {
        return array_filter(
            $this->types,
            function ($type): bool {
                return $type instanceof TCallable;
            }
        );
    }

    /**
     * @return array<string, TClosure>
     */
    public function getClosureTypes(): array
    {
        return array_filter(
            $this->types,
            function ($type): bool {
                return $type instanceof TClosure;
            }
        );
    }

    public function hasObject(): bool
    {
        return isset($this->types['object']);
    }

    public function hasObjectType(): bool
    {
        foreach ($this->types as $type) {
            if ($type->isObjectType()) {
                return true;
            }
        }

        return false;
    }

    public function isObjectType(): bool
    {
        foreach ($this->types as $type) {
            if (!$type->isObjectType()) {
                return false;
            }
        }

        return true;
    }

    public function hasNamedObjectType(): bool
    {
        foreach ($this->types as $type) {
            if ($type->isNamedObjectType()) {
                return true;
            }
        }

        return false;
    }

    public function isFormerStaticObject(): bool
    {
        foreach ($this->types as $type) {
            if (!$type instanceof TNamedObject
                || !$type->was_static
            ) {
                return false;
            }
        }

        return true;
    }

    public function hasFormerStaticObject(): bool
    {
        foreach ($this->types as $type) {
            if ($type instanceof TNamedObject
                && $type->was_static
            ) {
                return true;
            }
        }

        return false;
    }

    public function isNullable(): bool
    {
        if (isset($this->types['null'])) {
            return true;
        }

        foreach ($this->types as $type) {
            if ($type instanceof TTemplateParam && $type->as->isNullable()) {
                return true;
            }
        }

        return false;
    }

    public function isFalsable(): bool
    {
        if (isset($this->types['false'])) {
            return true;
        }

        foreach ($this->types as $type) {
            if ($type instanceof TTemplateParam && $type->as->isFalsable()) {
                return true;
            }
        }

        return false;
    }

    public function hasBool(): bool
    {
        return isset($this->types['bool']) || isset($this->types['false']) || isset($this->types['true']);
    }

    public function hasString(): bool
    {
        return isset($this->types['string'])
            || isset($this->types['class-string'])
            || isset($this->types['trait-string'])
            || isset($this->types['numeric-string'])
            || isset($this->types['callable-string'])
            || isset($this->types['array-key'])
            || $this->literal_string_types
            || $this->typed_class_strings;
    }

    public function hasLowercaseString(): bool
    {
        return isset($this->types['string'])
            && ($this->types['string'] instanceof TLowercaseString
                || $this->types['string'] instanceof TNonEmptyLowercaseString);
    }

    public function hasLiteralClassString(): bool
    {
        return count($this->typed_class_strings) > 0;
    }

    public function hasInt(): bool
    {
        return isset($this->types['int']) || isset($this->types['array-key']) || $this->literal_int_types
            || array_filter($this->types, function (Atomic $type) {
                return $type instanceof TIntRange;
            });
    }

    public function hasPositiveInt(): bool
    {
        return isset($this->types['int']) && $this->types['int'] instanceof TPositiveInt;
    }

    public function hasArrayKey(): bool
    {
        return isset($this->types['array-key']);
    }

    public function hasFloat(): bool
    {
        return isset($this->types['float']) || $this->literal_float_types;
    }

    public function hasScalar(): bool
    {
        return isset($this->types['scalar']);
    }

    public function hasNumeric(): bool
    {
        return isset($this->types['numeric']);
    }

    public function hasScalarType(): bool
    {
        return isset($this->types['int'])
            || isset($this->types['float'])
            || isset($this->types['string'])
            || isset($this->types['class-string'])
            || isset($this->types['trait-string'])
            || isset($this->types['bool'])
            || isset($this->types['false'])
            || isset($this->types['true'])
            || isset($this->types['numeric'])
            || isset($this->types['numeric-string'])
            || $this->literal_int_types
            || $this->literal_float_types
            || $this->literal_string_types
            || $this->typed_class_strings;
    }

    public function hasTemplate(): bool
    {
        return (bool) array_filter(
            $this->types,
            function (Atomic $type): bool {
                return $type instanceof TTemplateParam
                    || ($type instanceof TNamedObject
                        && $type->extra_types
                        && array_filter(
                            $type->extra_types,
                            function ($t): bool {
                                return $t instanceof TTemplateParam;
                            }
                        )
                    );
            }
        );
    }

    public function hasConditional(): bool
    {
        return (bool) array_filter(
            $this->types,
            function (Atomic $type): bool {
                return $type instanceof TConditional;
            }
        );
    }

    public function hasTemplateOrStatic(): bool
    {
        return (bool) array_filter(
            $this->types,
            function (Atomic $type): bool {
                return $type instanceof TTemplateParam
                    || ($type instanceof TNamedObject
                        && ($type->was_static
                            || ($type->extra_types
                                && array_filter(
                                    $type->extra_types,
                                    function ($t): bool {
                                        return $t instanceof TTemplateParam;
                                    }
                                )
                            )
                        )
                    );
            }
        );
    }

    public function hasMixed(): bool
    {
        return isset($this->types['mixed']);
    }

    public function isMixed(): bool
    {
        return isset($this->types['mixed']) && count($this->types) === 1;
    }

    public function isEmptyMixed(): bool
    {
        return isset($this->types['mixed'])
            && $this->types['mixed'] instanceof TEmptyMixed
            && count($this->types) === 1;
    }

    public function isVanillaMixed(): bool
    {
        return isset($this->types['mixed'])
            && get_class($this->types['mixed']) === TMixed::class
            && !$this->types['mixed']->from_loop_isset
            && count($this->types) === 1;
    }

    public function isArrayKey(): bool
    {
        return isset($this->types['array-key']) && count($this->types) === 1;
    }

    public function isNull(): bool
    {
        return count($this->types) === 1 && isset($this->types['null']);
    }

    public function isFalse(): bool
    {
        return count($this->types) === 1 && isset($this->types['false']);
    }

    public function isAlwaysFalsy(): bool
    {
        foreach ($this->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type->isFalsy()) {
                return false;
            }
        }

        return true;
    }

    public function isTrue(): bool
    {
        return count($this->types) === 1 && isset($this->types['true']);
    }

    public function isAlwaysTruthy(): bool
    {
        if ($this->possibly_undefined || $this->possibly_undefined_from_try) {
            return false;
        }

        foreach ($this->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type->isTruthy()) {
                return false;
            }
        }

        return true;
    }

    public function isVoid(): bool
    {
        return isset($this->types['void']) && count($this->types) === 1;
    }

    public function isNever(): bool
    {
        return isset($this->types['never']) && count($this->types) === 1;
    }

    public function isGenerator(): bool
    {
        return count($this->types) === 1
            && (($single_type = reset($this->types)) instanceof TNamedObject)
            && ($single_type->value === 'Generator');
    }

    public function isEmpty(): bool
    {
        return isset($this->types['empty']) && count($this->types) === 1;
    }

    public function substitute(Union $old_type, ?Union $new_type = null): void
    {
        if ($this->hasMixed() && !$this->isEmptyMixed()) {
            return;
        }

        if ($new_type && $new_type->ignore_nullable_issues) {
            $this->ignore_nullable_issues = true;
        }

        if ($new_type && $new_type->ignore_falsable_issues) {
            $this->ignore_falsable_issues = true;
        }

        foreach ($old_type->types as $old_type_part) {
            if (!$this->removeType($old_type_part->getKey())) {
                if ($old_type_part instanceof TFalse
                    && isset($this->types['bool'])
                    && !isset($this->types['true'])
                ) {
                    $this->removeType('bool');
                    $this->types['true'] = new TTrue;
                } elseif ($old_type_part instanceof TTrue
                    && isset($this->types['bool'])
                    && !isset($this->types['false'])
                ) {
                    $this->removeType('bool');
                    $this->types['false'] = new TFalse;
                } elseif (isset($this->types['iterable'])) {
                    if ($old_type_part instanceof TNamedObject
                        && $old_type_part->value === 'Traversable'
                        && !isset($this->types['array'])
                    ) {
                        $this->removeType('iterable');
                        $this->types['array'] = new TArray([Type::getArrayKey(), Type::getMixed()]);
                    }

                    if ($old_type_part instanceof TArray
                        && !isset($this->types['traversable'])
                    ) {
                        $this->removeType('iterable');
                        $this->types['traversable'] = new TNamedObject('Traversable');
                    }
                } elseif (isset($this->types['array-key'])) {
                    if ($old_type_part instanceof TString
                        && !isset($this->types['int'])
                    ) {
                        $this->removeType('array-key');
                        $this->types['int'] = new TInt();
                    }

                    if ($old_type_part instanceof TInt
                        && !isset($this->types['string'])
                    ) {
                        $this->removeType('array-key');
                        $this->types['string'] = new TString();
                    }
                }
            }
        }

        if ($new_type) {
            foreach ($new_type->types as $key => $new_type_part) {
                if (!isset($this->types[$key])
                    || ($new_type_part instanceof Scalar
                        && get_class($new_type_part) === get_class($this->types[$key]))
                ) {
                    $this->types[$key] = $new_type_part;
                } else {
                    $this->types[$key] = TypeCombiner::combine([$new_type_part, $this->types[$key]])->getSingleAtomic();
                }
            }
        } elseif (count($this->types) === 0) {
            $this->types['mixed'] = new TMixed();
        }

        $this->id = null;
    }

    public function isSingle(): bool
    {
        $type_count = count($this->types);

        $int_literal_count = count($this->literal_int_types);
        $string_literal_count = count($this->literal_string_types);
        $float_literal_count = count($this->literal_float_types);

        if (($int_literal_count && $string_literal_count)
            || ($int_literal_count && $float_literal_count)
            || ($string_literal_count && $float_literal_count)
        ) {
            return false;
        }

        if ($int_literal_count || $string_literal_count || $float_literal_count) {
            $type_count -= $int_literal_count + $string_literal_count + $float_literal_count - 1;
        }

        return $type_count === 1;
    }

    public function isSingleAndMaybeNullable(): bool
    {
        $is_nullable = isset($this->types['null']);

        $type_count = count($this->types);

        if ($type_count === 1 && $is_nullable) {
            return false;
        }

        $int_literal_count = count($this->literal_int_types);
        $string_literal_count = count($this->literal_string_types);
        $float_literal_count = count($this->literal_float_types);

        if (($int_literal_count && $string_literal_count)
            || ($int_literal_count && $float_literal_count)
            || ($string_literal_count && $float_literal_count)
        ) {
            return false;
        }

        if ($int_literal_count || $string_literal_count || $float_literal_count) {
            $type_count -= $int_literal_count + $string_literal_count + $float_literal_count - 1;
        }

        return ($type_count - (int) $is_nullable) === 1;
    }

    /**
     * @return bool true if this is an int
     */
    public function isInt(bool $check_templates = false): bool
    {
        return count(
            array_filter(
                $this->types,
                function ($type) use ($check_templates): bool {
                    return $type instanceof TInt
                        || ($check_templates
                            && $type instanceof TTemplateParam
                            && $type->as->isInt()
                        );
                }
            )
        ) === count($this->types);
    }

    /**
     * @return bool true if this is a float
     */
    public function isFloat(): bool
    {
        if (!$this->isSingle()) {
            return false;
        }

        return isset($this->types['float']) || $this->literal_float_types;
    }

    /**
     * @return bool true if this is a string
     */
    public function isString(bool $check_templates = false): bool
    {
        return count(
            array_filter(
                $this->types,
                function ($type) use ($check_templates): bool {
                    return $type instanceof TString
                        || ($check_templates
                            && $type instanceof TTemplateParam
                            && $type->as->isString()
                        );
                }
            )
        ) === count($this->types);
    }

    /**
     * @return bool true if this is a boolean
     */
    public function isBool(): bool
    {
        if (!$this->isSingle()) {
            return false;
        }

        return isset($this->types['bool']);
    }

    /**
     * @return bool true if this is an array
     */
    public function isArray(): bool
    {
        if (!$this->isSingle()) {
            return false;
        }

        return isset($this->types['array']);
    }

    /**
     * @return bool true if this is a string literal with only one possible value
     */
    public function isSingleStringLiteral(): bool
    {
        return count($this->types) === 1 && count($this->literal_string_types) === 1;
    }

    /**
     * @throws InvalidArgumentException if isSingleStringLiteral is false
     *
     * @return TLiteralString the only string literal represented by this union type
     */
    public function getSingleStringLiteral(): TLiteralString
    {
        if (count($this->types) !== 1 || count($this->literal_string_types) !== 1) {
            throw new InvalidArgumentException('Not a string literal');
        }

        return reset($this->literal_string_types);
    }

    public function allStringLiterals(): bool
    {
        foreach ($this->types as $atomic_key_type) {
            if (!$atomic_key_type instanceof TLiteralString) {
                return false;
            }
        }

        return true;
    }

    public function allIntLiterals(): bool
    {
        foreach ($this->types as $atomic_key_type) {
            if (!$atomic_key_type instanceof TLiteralInt) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-assert-if-true array<
     *     array-key,
     *     TLiteralString|TLiteralInt|TLiteralFloat|TFalse|TTrue
     * > $this->getAtomicTypes()
     */
    public function allSpecificLiterals(): bool
    {
        foreach ($this->types as $atomic_key_type) {
            if (!$atomic_key_type instanceof TLiteralString
                && !$atomic_key_type instanceof TLiteralInt
                && !$atomic_key_type instanceof TLiteralFloat
                && !$atomic_key_type instanceof TFalse
                && !$atomic_key_type instanceof TTrue
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-assert-if-true array<
     *     array-key,
     *     TLiteralString|TLiteralInt|TLiteralFloat|TNonspecificLiteralString|TNonSpecificLiteralInt|TFalse|TTrue
     * > $this->getAtomicTypes()
     */
    public function allLiterals(): bool
    {
        foreach ($this->types as $atomic_key_type) {
            if (!$atomic_key_type instanceof TLiteralString
                && !$atomic_key_type instanceof TLiteralInt
                && !$atomic_key_type instanceof TLiteralFloat
                && !$atomic_key_type instanceof TNonspecificLiteralString
                && !$atomic_key_type instanceof TNonspecificLiteralInt
                && !$atomic_key_type instanceof TFalse
                && !$atomic_key_type instanceof TTrue
            ) {
                return false;
            }
        }

        return true;
    }

    public function hasLiteralValue(): bool
    {
        return $this->literal_int_types
            || $this->literal_string_types
            || $this->literal_float_types
            || isset($this->types['false'])
            || isset($this->types['true']);
    }

    public function isSingleLiteral(): bool
    {
        return count($this->types) === 1
            && count($this->literal_int_types)
                + count($this->literal_string_types)
                + count($this->literal_float_types) === 1
        ;
    }

    /**
     * @return TLiteralInt|TLiteralString|TLiteralFloat
     */
    public function getSingleLiteral()
    {
        if (!$this->isSingleLiteral()) {
            throw new InvalidArgumentException("Not a single literal");
        }

        return ($literal = reset($this->literal_int_types)) !== false
            ? $literal
            : (($literal = reset($this->literal_string_types)) !== false
                ? $literal
                : reset($this->literal_float_types))
        ;
    }

    public function hasLiteralString(): bool
    {
        return count($this->literal_string_types) > 0;
    }

    public function hasLiteralInt(): bool
    {
        return count($this->literal_int_types) > 0;
    }

    /**
     * @return bool true if this is a int literal with only one possible value
     */
    public function isSingleIntLiteral(): bool
    {
        return count($this->types) === 1 && count($this->literal_int_types) === 1;
    }

    /**
     * @throws InvalidArgumentException if isSingleIntLiteral is false
     *
     * @return TLiteralInt the only int literal represented by this union type
     */
    public function getSingleIntLiteral(): TLiteralInt
    {
        if (count($this->types) !== 1 || count($this->literal_int_types) !== 1) {
            throw new InvalidArgumentException('Not an int literal');
        }

        return reset($this->literal_int_types);
    }

    /**
     * @param  array<string>    $suppressed_issues
     * @param  array<string, bool> $phantom_classes
     *
     */
    public function check(
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues,
        array $phantom_classes = [],
        bool $inferred = true,
        bool $inherited = false,
        bool $prevent_template_covariance = false,
        ?string $calling_method_id = null
    ): bool {
        if ($this->checked) {
            return true;
        }

        $checker = new TypeChecker(
            $source,
            $code_location,
            $suppressed_issues,
            $phantom_classes,
            $inferred,
            $inherited,
            $prevent_template_covariance,
            $calling_method_id
        );

        $checker->traverseArray($this->types);

        $this->checked = true;

        return !$checker->hasErrors();
    }

    /**
     * @param  array<string, mixed> $phantom_classes
     *
     */
    public function queueClassLikesForScanning(
        Codebase $codebase,
        ?FileStorage $file_storage = null,
        array $phantom_classes = []
    ): void {
        $scanner_visitor = new TypeScanner(
            $codebase->scanner,
            $file_storage,
            $phantom_classes
        );

        $scanner_visitor->traverseArray($this->types);
    }

    /**
     * @param  lowercase-string $fq_class_like_name
     */
    public function containsClassLike(string $fq_class_like_name): bool
    {
        $classlike_visitor = new ContainsClassLikeVisitor($fq_class_like_name);

        $classlike_visitor->traverseArray($this->types);

        return $classlike_visitor->matches();
    }

    public function containsAnyLiteral(): bool
    {
        $literal_visitor = new ContainsLiteralVisitor();

        $literal_visitor->traverseArray($this->types);

        return $literal_visitor->matches();
    }

    public function canContainObjectType(Codebase $codebase): bool
    {
        $object_type_visitor = new CanContainObjectTypeVisitor($codebase);

        $object_type_visitor->traverseArray($this->types);

        return $object_type_visitor->matches();
    }

    /**
     * @return list<TTemplateParam>
     */
    public function getTemplateTypes(): array
    {
        $template_type_collector = new TemplateTypeCollector();

        $template_type_collector->traverseArray($this->types);

        return $template_type_collector->getTemplateTypes();
    }

    public function setFromDocblock(): void
    {
        $this->from_docblock = true;

        (new FromDocblockSetter())->traverseArray($this->types);
    }

    public function replaceClassLike(string $old, string $new): void
    {
        foreach ($this->types as $key => $atomic_type) {
            $atomic_type->replaceClassLike($old, $new);

            $this->removeType($key);
            $this->addType($atomic_type);
        }
    }

    public function equals(Union $other_type, bool $ensure_source_equality = true): bool
    {
        if ($other_type === $this) {
            return true;
        }

        if ($other_type->id && $this->id && $other_type->id !== $this->id) {
            return false;
        }

        if ($this->possibly_undefined !== $other_type->possibly_undefined) {
            return false;
        }

        if ($this->had_template !== $other_type->had_template) {
            return false;
        }

        if ($this->possibly_undefined_from_try !== $other_type->possibly_undefined_from_try) {
            return false;
        }

        if ($this->from_calculation !== $other_type->from_calculation) {
            return false;
        }

        if ($this->initialized !== $other_type->initialized) {
            return false;
        }

        if ($ensure_source_equality && $this->from_docblock !== $other_type->from_docblock) {
            return false;
        }

        if (count($this->types) !== count($other_type->types)) {
            return false;
        }

        if ($this->parent_nodes !== $other_type->parent_nodes) {
            return false;
        }

        if ($this->different || $other_type->different) {
            return false;
        }

        $other_atomic_types = $other_type->types;

        foreach ($this->types as $key => $atomic_type) {
            if (!isset($other_atomic_types[$key])) {
                return false;
            }

            if (!$atomic_type->equals($other_atomic_types[$key], $ensure_source_equality)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, TLiteralString>
     */
    public function getLiteralStrings(): array
    {
        return $this->literal_string_types;
    }

    /**
     * @return array<string, TLiteralInt>
     */
    public function getLiteralInts(): array
    {
        return $this->literal_int_types;
    }

    /**
     * @return array<string, TIntRange>
     */
    public function getRangeInts(): array
    {
        $ranges = [];
        foreach ($this->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TIntRange) {
                $ranges[$atomic->getKey()] = $atomic;
            }
        }

        return $ranges;
    }

    /**
     * @return array<string, TLiteralFloat>
     */
    public function getLiteralFloats(): array
    {
        return $this->literal_float_types;
    }

    /**
     * @return array<string, Atomic>
     */
    public function getChildNodes(): array
    {
        return $this->types;
    }

    /**
     * @return bool true if this is a float literal with only one possible value
     */
    public function isSingleFloatLiteral(): bool
    {
        return count($this->types) === 1 && count($this->literal_float_types) === 1;
    }

    /**
     * @throws InvalidArgumentException if isSingleFloatLiteral is false
     *
     * @return TLiteralFloat the only float literal represented by this union type
     */
    public function getSingleFloatLiteral(): TLiteralFloat
    {
        if (count($this->types) !== 1 || count($this->literal_float_types) !== 1) {
            throw new InvalidArgumentException('Not a float literal');
        }

        return reset($this->literal_float_types);
    }

    public function hasLiteralFloat(): bool
    {
        return count($this->literal_float_types) > 0;
    }

    public function getSingleAtomic(): Atomic
    {
        return reset($this->types);
    }

    public function isUnionEmpty(): bool
    {
        return $this->types === [];
    }
}
