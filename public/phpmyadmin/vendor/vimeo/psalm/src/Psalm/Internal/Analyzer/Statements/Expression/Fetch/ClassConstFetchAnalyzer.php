<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Fetch;

use InvalidArgumentException;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\CircularReferenceException;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeNameOptions;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\CircularReference;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\DeprecatedConstant;
use Psalm\Issue\InaccessibleClassConstant;
use Psalm\Issue\InternalClass;
use Psalm\Issue\NonStaticSelfCall;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\UndefinedConstant;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Union;
use ReflectionProperty;

use function explode;
use function in_array;
use function strtolower;

/**
 * @internal
 */
class ClassConstFetchAnalyzer
{
    /**
     * @psalm-suppress ComplexMethod to be refactored. We should probably regroup the two big if about $stmt->class and
     * analyse the ::class int $stmt->name separately
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ClassConstFetch $stmt,
        Context $context
    ): bool {
        $codebase = $statements_analyzer->getCodebase();

        $statements_analyzer->node_data->setType($stmt, Type::getMixed());

        if ($stmt->class instanceof PhpParser\Node\Name) {
            $first_part_lc = strtolower($stmt->class->parts[0]);

            if ($first_part_lc === 'self' || $first_part_lc === 'static') {
                if (!$context->self) {
                    if (IssueBuffer::accepts(
                        new NonStaticSelfCall(
                            'Cannot use ' . $first_part_lc . ' outside class context',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return true;
                }

                $fq_class_name = $context->self;
            } elseif ($first_part_lc === 'parent') {
                $fq_class_name = $statements_analyzer->getParentFQCLN();

                if ($fq_class_name === null) {
                    if (IssueBuffer::accepts(
                        new ParentNotFound(
                            'Cannot check property fetch on parent as this class does not extend another',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return true;
                }
            } else {
                $fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                    $stmt->class,
                    $statements_analyzer->getAliases()
                );

                if ($stmt->name instanceof PhpParser\Node\Identifier) {
                    if ((!$context->inside_class_exists || $stmt->name->name !== 'class')
                        && !isset($context->phantom_classes[strtolower($fq_class_name)])
                    ) {
                        if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                            $statements_analyzer,
                            $fq_class_name,
                            new CodeLocation($statements_analyzer->getSource(), $stmt->class),
                            $context->self,
                            $context->calling_method_id,
                            $statements_analyzer->getSuppressedIssues(),
                            new ClassLikeNameOptions(false, true)
                        ) === false) {
                            return true;
                        }
                    }
                }
            }

            $fq_class_name_lc = strtolower($fq_class_name);

            $moved_class = false;

            if ($codebase->alter_code
                && !in_array($stmt->class->parts[0], ['parent', 'static'])
            ) {
                $moved_class = $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $statements_analyzer,
                    $stmt->class,
                    $fq_class_name,
                    $context->calling_method_id,
                    false,
                    $stmt->class->parts[0] === 'self'
                );
            }

            if ($codebase->classlikes->classExists($fq_class_name)) {
                $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);
            }

            if ($stmt->name instanceof PhpParser\Node\Identifier && $stmt->name->name === 'class') {
                if ($codebase->classlikes->classExists($fq_class_name)) {
                    $const_class_storage = $codebase->classlike_storage_provider->get($fq_class_name);
                    $fq_class_name = $const_class_storage->name;

                    if ($const_class_storage->deprecated && $fq_class_name !== $context->self) {
                        IssueBuffer::maybeAdd(
                            new DeprecatedClass(
                                'Class ' . $fq_class_name . ' is deprecated',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $fq_class_name
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        );
                    }
                }

                if ($first_part_lc === 'static') {
                    $static_named_object = new TNamedObject($fq_class_name);
                    $static_named_object->was_static = true;

                    $statements_analyzer->node_data->setType(
                        $stmt,
                        new Union([
                            new TClassString($fq_class_name, $static_named_object)
                        ])
                    );
                } else {
                    $statements_analyzer->node_data->setType($stmt, Type::getLiteralClassString($fq_class_name, true));
                }

                if ($codebase->store_node_types
                    && !$context->collect_initializations
                    && !$context->collect_mutations
                ) {
                    $codebase->analyzer->addNodeReference(
                        $statements_analyzer->getFilePath(),
                        $stmt->class,
                        $fq_class_name
                    );
                }

                return true;
            }

            // if we're ignoring that the class doesn't exist, exit anyway
            if (!$codebase->classlikes->classOrInterfaceOrEnumExists($fq_class_name)) {
                return true;
            }

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->class,
                    $fq_class_name
                );
            }

            if (!$stmt->name instanceof PhpParser\Node\Identifier) {
                return true;
            }

            $const_id = $fq_class_name . '::' . $stmt->name;

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->name,
                    $const_id
                );
            }

            $const_class_storage = $codebase->classlike_storage_provider->get($fq_class_name);
            if ($const_class_storage->is_enum) {
                $case = $const_class_storage->enum_cases[(string)$stmt->name] ?? null;
                if ($case && $case->deprecated) {
                    IssueBuffer::maybeAdd(
                        new DeprecatedConstant(
                            "Enum Case $const_id is marked as deprecated",
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                }
            }

            if ($fq_class_name === $context->self
                || (
                    $statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer &&
                    $fq_class_name === $statements_analyzer->getSource()->getFQCLN()
                )
            ) {
                $class_visibility = ReflectionProperty::IS_PRIVATE;
            } elseif ($context->self &&
                ($codebase->classlikes->classExtends($context->self, $fq_class_name)
                    || $codebase->classlikes->classExtends($fq_class_name, $context->self))
            ) {
                $class_visibility = ReflectionProperty::IS_PROTECTED;
            } else {
                $class_visibility = ReflectionProperty::IS_PUBLIC;
            }

            try {
                $class_constant_type = $codebase->classlikes->getClassConstantType(
                    $fq_class_name,
                    $stmt->name->name,
                    $class_visibility,
                    $statements_analyzer
                );
            } catch (InvalidArgumentException $_) {
                return true;
            } catch (CircularReferenceException $e) {
                IssueBuffer::maybeAdd(
                    new CircularReference(
                        'Constant ' . $const_id . ' contains a circular reference',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );

                return true;
            }

            if (!$class_constant_type) {
                if ($fq_class_name !== $context->self) {
                    $class_constant_type = $codebase->classlikes->getClassConstantType(
                        $fq_class_name,
                        $stmt->name->name,
                        ReflectionProperty::IS_PRIVATE,
                        $statements_analyzer
                    );
                }

                if ($class_constant_type) {
                    IssueBuffer::maybeAdd(
                        new InaccessibleClassConstant(
                            'Constant ' . $const_id . ' is not visible in this context',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                } elseif ($context->check_consts) {
                    IssueBuffer::maybeAdd(
                        new UndefinedConstant(
                            'Constant ' . $const_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                }

                return true;
            }

            if ($context->calling_method_id) {
                $codebase->file_reference_provider->addMethodReferenceToClassMember(
                    $context->calling_method_id,
                    $fq_class_name_lc . '::' . $stmt->name->name,
                    false
                );
            }

            $declaring_const_id = $fq_class_name_lc . '::' . $stmt->name->name;

            if ($codebase->alter_code && !$moved_class) {
                foreach ($codebase->class_constant_transforms as $original_pattern => $transformation) {
                    if ($declaring_const_id === $original_pattern) {
                        [$new_fq_class_name, $new_const_name] = explode('::', $transformation);

                        $file_manipulations = [];

                        if (strtolower($new_fq_class_name) !== $fq_class_name_lc) {
                            $file_manipulations[] = new FileManipulation(
                                (int) $stmt->class->getAttribute('startFilePos'),
                                (int) $stmt->class->getAttribute('endFilePos') + 1,
                                Type::getStringFromFQCLN(
                                    $new_fq_class_name,
                                    $statements_analyzer->getNamespace(),
                                    $statements_analyzer->getAliasedClassesFlipped(),
                                    null
                                )
                            );
                        }

                        $file_manipulations[] = new FileManipulation(
                            (int) $stmt->name->getAttribute('startFilePos'),
                            (int) $stmt->name->getAttribute('endFilePos') + 1,
                            $new_const_name
                        );

                        FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
                    }
                }
            }

            if ($context->self
                && !$context->collect_initializations
                && !$context->collect_mutations
                && !NamespaceAnalyzer::isWithinAny($context->self, $const_class_storage->internal)
            ) {
                IssueBuffer::maybeAdd(
                    new InternalClass(
                        $fq_class_name . ' is internal to '
                            . InternalClass::listToPhrase($const_class_storage->internal)
                            . ' but called from ' . $context->self,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $fq_class_name
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }

            if ($const_class_storage->deprecated && $fq_class_name !== $context->self) {
                IssueBuffer::maybeAdd(
                    new DeprecatedClass(
                        'Class ' . $fq_class_name . ' is deprecated',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $fq_class_name
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            } elseif (isset($const_class_storage->constants[$stmt->name->name])
                && $const_class_storage->constants[$stmt->name->name]->deprecated
            ) {
                IssueBuffer::maybeAdd(
                    new DeprecatedConstant(
                        'Constant ' . $const_id . ' is deprecated',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }

            if ($first_part_lc !== 'static' || $const_class_storage->final) {
                $stmt_type = clone $class_constant_type;

                $statements_analyzer->node_data->setType($stmt, $stmt_type);
                $context->vars_in_scope[$const_id] = $stmt_type;
            }

            return true;
        }

        $was_inside_general_use = $context->inside_general_use;
        $context->inside_general_use = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->class, $context) === false) {
            $context->inside_general_use = $was_inside_general_use;

            return false;
        }

        $context->inside_general_use = $was_inside_general_use;

        $lhs_type = $statements_analyzer->node_data->getType($stmt->class);

        if ($lhs_type === null) {
            return true;
        }

        if ($stmt->name instanceof PhpParser\Node\Identifier && $stmt->name->name === 'class') {
            $class_string_types = [];

            $has_mixed_or_object = false;

            foreach ($lhs_type->getAtomicTypes() as $lhs_atomic_type) {
                if ($lhs_atomic_type instanceof TNamedObject) {
                    $class_string_types[] = new TClassString(
                        $lhs_atomic_type->value,
                        clone $lhs_atomic_type
                    );
                } elseif ($lhs_atomic_type instanceof TTemplateParam
                    && $lhs_atomic_type->as->isSingle()) {
                    $as_atomic_type = $lhs_atomic_type->as->getSingleAtomic();

                    if ($as_atomic_type instanceof TObject) {
                        $class_string_types[] = new TTemplateParamClass(
                            $lhs_atomic_type->param_name,
                            'object',
                            null,
                            $lhs_atomic_type->defining_class
                        );
                    } elseif ($as_atomic_type instanceof TNamedObject) {
                        $class_string_types[] = new TTemplateParamClass(
                            $lhs_atomic_type->param_name,
                            $as_atomic_type->value,
                            $as_atomic_type,
                            $lhs_atomic_type->defining_class
                        );
                    }
                } elseif ($lhs_atomic_type instanceof TObject
                    || $lhs_atomic_type instanceof TMixed
                ) {
                    $has_mixed_or_object = true;
                }
            }

            if ($has_mixed_or_object) {
                $statements_analyzer->node_data->setType($stmt, new Union([new TClassString()]));
            } elseif ($class_string_types) {
                $statements_analyzer->node_data->setType($stmt, new Union($class_string_types));
            }

            return true;
        }

        if ($stmt->class instanceof PhpParser\Node\Expr\Variable) {
            $fq_class_name = null;
            $lhs_type_definite_class = null;
            if ($lhs_type->isSingle()) {
                $atomic_type = $lhs_type->getSingleAtomic();
                if ($atomic_type instanceof TNamedObject) {
                    $fq_class_name = $atomic_type->value;
                    $lhs_type_definite_class = $atomic_type->definite_class;
                } elseif ($atomic_type instanceof TLiteralClassString) {
                    $fq_class_name = $atomic_type->value;
                    $lhs_type_definite_class = $atomic_type->definite_class;
                }
            }

            if ($fq_class_name === null || $lhs_type_definite_class === null) {
                return true;
            }

            if ($codebase->classlikes->classExists($fq_class_name)) {
                $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);
            }

            $moved_class = false;

            if ($codebase->alter_code) {
                $moved_class = $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $statements_analyzer,
                    $stmt->class,
                    $fq_class_name,
                    $context->calling_method_id
                );
            }

            // if we're ignoring that the class doesn't exist, exit anyway
            if (!$codebase->classlikes->classOrInterfaceOrEnumExists($fq_class_name)) {
                return true;
            }

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->class,
                    $fq_class_name
                );
            }

            if (!$stmt->name instanceof PhpParser\Node\Identifier) {
                return true;
            }

            $const_id = $fq_class_name . '::' . $stmt->name;

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->name,
                    $const_id
                );
            }

            $const_class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

            if ($fq_class_name === $context->self
                || (
                    $statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer &&
                    $fq_class_name === $statements_analyzer->getSource()->getFQCLN()
                )
            ) {
                $class_visibility = ReflectionProperty::IS_PRIVATE;
            } elseif ($context->self &&
                ($codebase->classlikes->classExtends($context->self, $fq_class_name)
                    || $codebase->classlikes->classExtends($fq_class_name, $context->self))
            ) {
                $class_visibility = ReflectionProperty::IS_PROTECTED;
            } else {
                $class_visibility = ReflectionProperty::IS_PUBLIC;
            }

            try {
                $class_constant_type = $codebase->classlikes->getClassConstantType(
                    $fq_class_name,
                    $stmt->name->name,
                    $class_visibility,
                    $statements_analyzer
                );
            } catch (InvalidArgumentException $_) {
                return true;
            } catch (CircularReferenceException $e) {
                IssueBuffer::maybeAdd(
                    new CircularReference(
                        'Constant ' . $const_id . ' contains a circular reference',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );

                return true;
            }

            if (!$class_constant_type) {
                if ($fq_class_name !== $context->self) {
                    $class_constant_type = $codebase->classlikes->getClassConstantType(
                        $fq_class_name,
                        $stmt->name->name,
                        ReflectionProperty::IS_PRIVATE,
                        $statements_analyzer
                    );
                }

                if ($class_constant_type) {
                    IssueBuffer::maybeAdd(
                        new InaccessibleClassConstant(
                            'Constant ' . $const_id . ' is not visible in this context',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                } elseif ($context->check_consts) {
                    IssueBuffer::maybeAdd(
                        new UndefinedConstant(
                            'Constant ' . $const_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                }

                return true;
            }

            if ($context->calling_method_id) {
                $codebase->file_reference_provider->addMethodReferenceToClassMember(
                    $context->calling_method_id,
                    strtolower($fq_class_name) . '::' . $stmt->name->name,
                    false
                );
            }

            $declaring_const_id = strtolower($fq_class_name) . '::' . $stmt->name->name;

            if ($codebase->alter_code && !$moved_class) {
                foreach ($codebase->class_constant_transforms as $original_pattern => $transformation) {
                    if ($declaring_const_id === $original_pattern) {
                        [, $new_const_name] = explode('::', $transformation);

                        $file_manipulations = [];

                        $file_manipulations[] = new FileManipulation(
                            (int) $stmt->name->getAttribute('startFilePos'),
                            (int) $stmt->name->getAttribute('endFilePos') + 1,
                            $new_const_name
                        );

                        FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
                    }
                }
            }

            if ($context->self
                && !$context->collect_initializations
                && !$context->collect_mutations
                && !NamespaceAnalyzer::isWithinAny($context->self, $const_class_storage->internal)
            ) {
                IssueBuffer::maybeAdd(
                    new InternalClass(
                        $fq_class_name . ' is internal to '
                            . InternalClass::listToPhrase($const_class_storage->internal)
                            . ' but called from ' . $context->self,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $fq_class_name
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }

            if ($const_class_storage->deprecated && $fq_class_name !== $context->self) {
                IssueBuffer::maybeAdd(
                    new DeprecatedClass(
                        'Class ' . $fq_class_name . ' is deprecated',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $fq_class_name
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            } elseif (isset($const_class_storage->constants[$stmt->name->name])
                && $const_class_storage->constants[$stmt->name->name]->deprecated
            ) {
                IssueBuffer::maybeAdd(
                    new DeprecatedConstant(
                        'Constant ' . $const_id . ' is deprecated',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }

            if ($const_class_storage->final || $lhs_type_definite_class === true) {
                $stmt_type = clone $class_constant_type;

                $statements_analyzer->node_data->setType($stmt, $stmt_type);
                $context->vars_in_scope[$const_id] = $stmt_type;
            }

            return true;
        }

        return true;
    }

    public static function analyzeClassConstAssignment(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\ClassConst $stmt,
        Context $context
    ): void {
        foreach ($stmt->consts as $const) {
            ExpressionAnalyzer::analyze($statements_analyzer, $const->value, $context);
        }
    }
}
