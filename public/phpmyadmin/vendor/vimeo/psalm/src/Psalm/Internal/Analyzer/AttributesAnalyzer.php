<?php

namespace Psalm\Internal\Analyzer;

use Generator;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Expression;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\ConstantTypeResolver;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Issue\InvalidAttribute;
use Psalm\Issue\UndefinedClass;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\HasAttributesInterface;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Union;

use function array_shift;
use function array_values;
use function assert;
use function count;
use function reset;
use function strtolower;

class AttributesAnalyzer
{
    private const TARGET_DESCRIPTIONS = [
        1 => 'class',
        2 => 'function',
        4 => 'method',
        8 => 'property',
        16 => 'class constant',
        32 => 'function/method parameter',
        40 => 'promoted property',
    ];

    // Copied from Attribute class since that class might not exist at runtime
    public const TARGET_CLASS = 1;
    public const TARGET_FUNCTION = 2;
    public const TARGET_METHOD = 4;
    public const TARGET_PROPERTY = 8;
    public const TARGET_CLASS_CONSTANT = 16;
    public const TARGET_PARAMETER = 32;
    public const TARGET_ALL = 63;
    public const IS_REPEATABLE = 64;

    /**
     * @param array<array-key, AttributeGroup> $attribute_groups
     * @param key-of<self::TARGET_DESCRIPTIONS> $target
     * @param array<array-key, string> $suppressed_issues
     */
    public static function analyze(
        SourceAnalyzer $source,
        Context $context,
        HasAttributesInterface $storage,
        array $attribute_groups,
        int $target,
        array $suppressed_issues
    ): void {
        $codebase = $source->getCodebase();
        $appearing_non_repeatable_attributes = [];
        foreach (self::iterateAttributeNodes($attribute_groups) as $attribute) {
            if ($attribute->name instanceof FullyQualified) {
                $fq_attribute_name = (string) $attribute->name;
            } else {
                $fq_attribute_name = ClassLikeAnalyzer::getFQCLNFromNameObject($attribute->name, $source->getAliases());
            }

            $attribute_name = (string) $attribute->name;
            $attribute_name_location = new CodeLocation($source, $attribute->name);

            $attribute_class_storage = $codebase->classlikes->classExists($fq_attribute_name)
                ? $codebase->classlike_storage_provider->get($fq_attribute_name)
                : null;

            $attribute_class_flags = self::getAttributeClassFlags(
                $source,
                $attribute_name,
                $fq_attribute_name,
                $attribute_name_location,
                $attribute_class_storage,
                $suppressed_issues
            );

            self::analyzeAttributeConstruction(
                $source,
                $context,
                $fq_attribute_name,
                $attribute,
                $suppressed_issues,
                $storage instanceof ClassLikeStorage ? $storage : null
            );

            if (($attribute_class_flags & self::IS_REPEATABLE) === 0) {
                // Not IS_REPEATABLE
                if (isset($appearing_non_repeatable_attributes[$fq_attribute_name])) {
                    IssueBuffer::maybeAdd(
                        new InvalidAttribute(
                            "Attribute {$attribute_name} is not repeatable",
                            $attribute_name_location
                        ),
                        $suppressed_issues
                    );
                }
                $appearing_non_repeatable_attributes[$fq_attribute_name] = true;
            }

            if (($attribute_class_flags & $target) === 0) {
                IssueBuffer::maybeAdd(
                    new InvalidAttribute(
                        "Attribute {$attribute_name} cannot be used on a "
                            . self::TARGET_DESCRIPTIONS[$target],
                        $attribute_name_location
                    ),
                    $suppressed_issues
                );
            }
        }
    }

    /**
     * @param array<array-key, string> $suppressed_issues
     */
    private static function analyzeAttributeConstruction(
        SourceAnalyzer $source,
        Context $context,
        string $fq_attribute_name,
        Attribute $attribute,
        array $suppressed_issues,
        ?ClassLikeStorage $classlike_storage = null
    ): void {
        $attribute_name_location = new CodeLocation($source, $attribute->name);

        if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
            $source,
            $fq_attribute_name,
            $attribute_name_location,
            null,
            null,
            $suppressed_issues,
            new ClassLikeNameOptions(
                false,
                false,
                false,
                false,
                false,
                true
            )
        ) === false) {
            return;
        }

        if (strtolower($fq_attribute_name) === 'attribute' && $classlike_storage) {
            if ($classlike_storage->is_trait) {
                IssueBuffer::maybeAdd(
                    new InvalidAttribute(
                        'Traits cannot act as attribute classes',
                        $attribute_name_location
                    ),
                    $suppressed_issues
                );
            } elseif ($classlike_storage->is_interface) {
                IssueBuffer::maybeAdd(
                    new InvalidAttribute(
                        'Interfaces cannot act as attribute classes',
                        $attribute_name_location
                    ),
                    $suppressed_issues
                );
            } elseif ($classlike_storage->abstract) {
                IssueBuffer::maybeAdd(
                    new InvalidAttribute(
                        'Abstract classes cannot act as attribute classes',
                        $attribute_name_location
                    ),
                    $suppressed_issues
                );
            } elseif (isset($classlike_storage->methods['__construct'])
                && $classlike_storage->methods['__construct']->visibility !== ClassLikeAnalyzer::VISIBILITY_PUBLIC
            ) {
                IssueBuffer::maybeAdd(
                    new InvalidAttribute(
                        'Classes with protected/private constructors cannot act as attribute classes',
                        $attribute_name_location
                    ),
                    $suppressed_issues
                );
            } elseif ($classlike_storage->is_enum) {
                IssueBuffer::maybeAdd(
                    new InvalidAttribute(
                        'Enums cannot act as attribute classes',
                        $attribute_name_location
                    ),
                    $suppressed_issues
                );
            }
        }

        $statements_analyzer = new StatementsAnalyzer(
            $source,
            new NodeDataProvider()
        );
        $statements_analyzer->addSuppressedIssues(array_values($suppressed_issues));

        $had_returned = $context->has_returned;
        $context->has_returned = false;

        IssueBuffer::startRecording();
        $statements_analyzer->analyze(
            [new Expression(new New_($attribute->name, $attribute->args, $attribute->getAttributes()))],
            // Use a new Context for the Attribute attribute so that it can't access `self`
            strtolower($fq_attribute_name) === "attribute" ? new Context() : $context
        );
        $context->has_returned = $had_returned;

        $issues = IssueBuffer::clearRecordingLevel();
        IssueBuffer::stopRecording();
        foreach ($issues as $issue) {
            if ($issue instanceof UndefinedClass && $issue->fq_classlike_name === $fq_attribute_name) {
                // Remove UndefinedClass for the attribute, since we already added UndefinedAttribute
                continue;
            }
            IssueBuffer::bubbleUp($issue);
        }
    }

    /**
     * @param array<array-key, string> $suppressed_issues
     */
    private static function getAttributeClassFlags(
        SourceAnalyzer $source,
        string $attribute_name,
        string $fq_attribute_name,
        CodeLocation $attribute_name_location,
        ?ClassLikeStorage $attribute_class_storage,
        array $suppressed_issues
    ): int {
        if (strtolower($fq_attribute_name) === "attribute") {
            // We override this here because we still want to analyze attributes
            // for PHP 7.4 when the Attribute class doesn't yet exist.
            return self::TARGET_CLASS;
        }

        if ($attribute_class_storage === null) {
            return self::TARGET_ALL; // Defaults to TARGET_ALL
        }

        foreach ($attribute_class_storage->attributes as $attribute_attribute) {
            if ($attribute_attribute->fq_class_name === 'Attribute') {
                if (!$attribute_attribute->args) {
                    return self::TARGET_ALL; // Defaults to TARGET_ALL
                }

                $first_arg = reset($attribute_attribute->args);

                $first_arg_type = $first_arg->type;

                if ($first_arg_type instanceof UnresolvedConstantComponent) {
                    $first_arg_type = new Union([
                        ConstantTypeResolver::resolve(
                            $source->getCodebase()->classlikes,
                            $first_arg_type,
                            $source instanceof StatementsAnalyzer ? $source : null
                        )
                    ]);
                }

                if (!$first_arg_type->isSingleIntLiteral()) {
                    return self::TARGET_ALL; // Fall back to default if it's invalid
                }

                return $first_arg_type->getSingleIntLiteral()->value;
            }
        }

        IssueBuffer::maybeAdd(
            new InvalidAttribute(
                "The class {$attribute_name} doesn't have the Attribute attribute",
                $attribute_name_location
            ),
            $suppressed_issues
        );

        return self::TARGET_ALL; // Fall back to default if it's invalid
    }

    /**
     * @param iterable<AttributeGroup> $attribute_groups
     *
     * @return Generator<int, Attribute>
     */
    private static function iterateAttributeNodes(iterable $attribute_groups): Generator
    {
        foreach ($attribute_groups as $attribute_group) {
            foreach ($attribute_group->attrs as $attribute) {
                yield $attribute;
            }
        }
    }

    /**
     * Analyze Reflection getAttributes method calls.

     * @param list<Arg> $args
     */
    public static function analyzeGetAttributes(
        StatementsAnalyzer $statements_analyzer,
        string $method_id,
        array $args
    ): void {
        if (count($args) !== 1) {
            // We skip this analysis if $flags is specified on getAttributes, since the only option
            // is ReflectionAttribute::IS_INSTANCEOF, which causes getAttributes to return children.
            // When returning children we don't want to limit this since a child could add a target.
            return;
        }

        switch ($method_id) {
            case "ReflectionClass::getattributes":
                $target = self::TARGET_CLASS;
                break;
            case "ReflectionFunction::getattributes":
                $target = self::TARGET_FUNCTION;
                break;
            case "ReflectionMethod::getattributes":
                $target = self::TARGET_METHOD;
                break;
            case "ReflectionProperty::getattributes":
                $target = self::TARGET_PROPERTY;
                break;
            case "ReflectionClassConstant::getattributes":
                $target = self::TARGET_CLASS_CONSTANT;
                break;
            case "ReflectionParameter::getattributes":
                $target = self::TARGET_PARAMETER;
                break;
            default:
                return;
        }

        $arg = $args[0];
        if ($arg->name !== null) {
            for (; !empty($args) && ($arg->name->name ?? null) !== "name"; $arg = array_shift($args));
            if ($arg->name->name ?? null !== "name") {
                // No named argument for "name" parameter
                return;
            }
        }

        $arg_type = $statements_analyzer->getNodeTypeProvider()->getType($arg->value);
        if ($arg_type === null || !$arg_type->isSingle() || !$arg_type->hasLiteralString()) {
            return;
        }

        $class_string = $arg_type->getSingleAtomic();
        assert($class_string instanceof TLiteralString);

        $codebase = $statements_analyzer->getCodebase();

        if (!$codebase->classExists($class_string->value)) {
            return;
        }

        $class_storage = $codebase->classlike_storage_provider->get($class_string->value);
        $arg_location = new CodeLocation($statements_analyzer, $arg);
        $class_attribute_target = self::getAttributeClassFlags(
            $statements_analyzer,
            $class_string->value,
            $class_string->value,
            $arg_location,
            $class_storage,
            $statements_analyzer->getSuppressedIssues()
        );

        if (($class_attribute_target & $target) === 0) {
            IssueBuffer::maybeAdd(
                new InvalidAttribute(
                    "Attribute {$class_string->value} cannot be used on a "
                        . self::TARGET_DESCRIPTIONS[$target],
                    $arg_location
                ),
                $statements_analyzer->getSuppressedIssues()
            );
        }
    }
}
