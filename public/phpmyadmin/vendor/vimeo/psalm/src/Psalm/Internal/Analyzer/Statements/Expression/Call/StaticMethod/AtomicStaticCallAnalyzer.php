<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\StaticMethod;

use Exception;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeNameOptions;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentMapPopulator;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\MethodVisibilityAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\StaticCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\AtomicPropertyFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InternalClass;
use Psalm\Issue\InvalidStringClass;
use Psalm\Issue\MixedMethodCall;
use Psalm\Issue\UndefinedClass;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\VirtualArray;
use Psalm\Node\Expr\VirtualArrayItem;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Expr\VirtualVariable;
use Psalm\Node\Scalar\VirtualString;
use Psalm\Node\VirtualArg;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TDependentGetClass;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_filter;
use function array_map;
use function array_values;
use function assert;
use function count;
use function in_array;
use function strtolower;

class AtomicStaticCallAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $stmt,
        Context $context,
        Atomic $lhs_type_part,
        bool $ignore_nullable_issues,
        bool &$moved_call,
        bool &$has_mock,
        bool &$has_existing_method
    ): void {
        $intersection_types = [];

        if ($lhs_type_part instanceof TNamedObject) {
            $fq_class_name = $lhs_type_part->value;

            if (!ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $statements_analyzer,
                $fq_class_name,
                new CodeLocation($statements_analyzer, $stmt->class),
                !$context->collect_initializations
                    && !$context->collect_mutations
                    ? $context->self
                    : null,
                !$context->collect_initializations
                    && !$context->collect_mutations
                    ? $context->calling_method_id
                    : null,
                $statements_analyzer->getSuppressedIssues(),
                new ClassLikeNameOptions(
                    $stmt->class instanceof PhpParser\Node\Name
                        && count($stmt->class->parts) === 1
                        && in_array(strtolower($stmt->class->parts[0]), ['self', 'static'], true)
                )
            )) {
                return;
            }

            $intersection_types = $lhs_type_part->extra_types;
        } elseif ($lhs_type_part instanceof TClassString
            && $lhs_type_part->as_type
        ) {
            $fq_class_name = $lhs_type_part->as_type->value;

            if (!ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $statements_analyzer,
                $fq_class_name,
                new CodeLocation($statements_analyzer, $stmt->class),
                $context->self,
                $context->calling_method_id,
                $statements_analyzer->getSuppressedIssues()
            )) {
                return;
            }

            $intersection_types = $lhs_type_part->as_type->extra_types;
        } elseif ($lhs_type_part instanceof TDependentGetClass
            && !$lhs_type_part->as_type->hasObject()
        ) {
            $fq_class_name = 'object';

            if ($lhs_type_part->as_type->hasObjectType()
                && $lhs_type_part->as_type->isSingle()
            ) {
                foreach ($lhs_type_part->as_type->getAtomicTypes() as $typeof_type_atomic) {
                    if ($typeof_type_atomic instanceof TNamedObject) {
                        $fq_class_name = $typeof_type_atomic->value;
                    }
                }
            }

            if ($fq_class_name === 'object') {
                return;
            }
        } elseif ($lhs_type_part instanceof TLiteralClassString) {
            $fq_class_name = $lhs_type_part->value;

            if (!ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $statements_analyzer,
                $fq_class_name,
                new CodeLocation($statements_analyzer, $stmt->class),
                $context->self,
                $context->calling_method_id,
                $statements_analyzer->getSuppressedIssues()
            )) {
                return;
            }
        } elseif ($lhs_type_part instanceof TTemplateParam
            && !$lhs_type_part->as->isMixed()
            && !$lhs_type_part->as->hasObject()
        ) {
            $fq_class_name = null;

            foreach ($lhs_type_part->as->getAtomicTypes() as $generic_param_type) {
                if (!$generic_param_type instanceof TNamedObject) {
                    return;
                }

                $fq_class_name = $generic_param_type->value;
                break;
            }

            if (!$fq_class_name) {
                IssueBuffer::maybeAdd(
                    new UndefinedClass(
                        'Type ' . $lhs_type_part->as . ' cannot be called as a class',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        (string) $lhs_type_part
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );

                return;
            }
        } else {
            self::handleNonObjectCall(
                $statements_analyzer,
                $stmt,
                $context,
                $lhs_type_part,
                $ignore_nullable_issues
            );

            return;
        }

        $codebase = $statements_analyzer->getCodebase();

        $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);

        $is_mock = ExpressionAnalyzer::isMock($fq_class_name);

        $has_mock = $has_mock || $is_mock;

        if ($stmt->name instanceof PhpParser\Node\Identifier && !$is_mock) {
            self::handleNamedCall(
                $statements_analyzer,
                $stmt,
                $stmt->name,
                $context,
                $lhs_type_part,
                $intersection_types ?: [],
                $fq_class_name,
                $moved_call,
                $has_existing_method
            );
        } else {
            if ($stmt->name instanceof PhpParser\Node\Expr) {
                $was_inside_general_use = $context->inside_general_use;
                $context->inside_general_use = true;

                ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context);

                $context->inside_general_use = $was_inside_general_use;
            }

            if (!$context->ignore_variable_method) {
                $codebase->analyzer->addMixedMemberName(
                    strtolower($fq_class_name) . '::',
                    $context->calling_method_id ?: $statements_analyzer->getFileName()
                );
            }

            if ($stmt->isFirstClassCallable()) {
                $return_type_candidate = null;
                if (!$stmt->name instanceof PhpParser\Node\Identifier) {
                    $method_name_type = $statements_analyzer->node_data->getType($stmt->name);
                    if ($method_name_type && $method_name_type->isSingleStringLiteral()) {
                        $method_identifier = new MethodIdentifier(
                            $fq_class_name,
                            strtolower($method_name_type->getSingleStringLiteral()->value)
                        );
                        //the call to methodExists will register that the method was called from somewhere
                        if ($codebase->methods->methodExists(
                            $method_identifier,
                            $context->calling_method_id,
                            null,
                            $statements_analyzer,
                            $statements_analyzer->getFilePath(),
                            true,
                            $context->insideUse()
                        )) {
                            $method_storage = $codebase->methods->getStorage($method_identifier);

                            $return_type_candidate = new Union([new TClosure(
                                'Closure',
                                $method_storage->params,
                                $method_storage->return_type,
                                $method_storage->pure
                            )]);
                        }
                    }
                }

                $statements_analyzer->node_data->setType($stmt, $return_type_candidate ?? Type::getClosure());

                return;
            }

            if (ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                null,
                null,
                true,
                $context
            ) === false) {
                return;
            }
        }

        if ($codebase->alter_code
            && $fq_class_name
            && !$moved_call
            && $stmt->class instanceof PhpParser\Node\Name
            && !in_array($stmt->class->parts[0], ['parent', 'static'])
        ) {
            $codebase->classlikes->handleClassLikeReferenceInMigration(
                $codebase,
                $statements_analyzer,
                $stmt->class,
                $fq_class_name,
                $context->calling_method_id,
                false,
                $stmt->class->parts[0] === 'self'
            );
        }
    }

    /**
     * @psalm-suppress UnusedReturnValue not used but seems important
     * @psalm-suppress ComplexMethod to be refactored
     */
    private static function handleNamedCall(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $stmt,
        PhpParser\Node\Identifier $stmt_name,
        Context $context,
        Atomic $lhs_type_part,
        array $intersection_types,
        string $fq_class_name,
        bool &$moved_call,
        bool &$has_existing_method
    ): bool {
        $codebase = $statements_analyzer->getCodebase();

        $method_name_lc = strtolower($stmt_name->name);
        $method_id = new MethodIdentifier($fq_class_name, $method_name_lc);

        $cased_method_id = $fq_class_name . '::' . $stmt_name->name;

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            ArgumentMapPopulator::recordArgumentPositions(
                $statements_analyzer,
                $stmt,
                $codebase,
                (string) $method_id
            );
        }

        if ($intersection_types
            && !$codebase->methods->methodExists($method_id)
        ) {
            foreach ($intersection_types as $intersection_type) {
                if (!$intersection_type instanceof TNamedObject) {
                    continue;
                }

                $intersection_method_id = new MethodIdentifier(
                    $intersection_type->value,
                    $method_name_lc
                );

                if ($codebase->methods->methodExists($intersection_method_id)) {
                    $method_id = $intersection_method_id;
                    $cased_method_id = $intersection_type->value . '::' . $stmt_name->name;
                    $fq_class_name = $intersection_type->value;
                    break;
                }
            }
        }

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $naive_method_exists = $codebase->methods->methodExists(
            $method_id,
            !$context->collect_initializations
                && !$context->collect_mutations
                ? $context->calling_method_id
                : null,
            $codebase->collect_locations
                ? new CodeLocation($statements_analyzer, $stmt_name)
                : null,
            $statements_analyzer,
            $statements_analyzer->getFilePath(),
            false,
            $context->insideUse()
        );

        $fake_method_exists = false;

        if (!$naive_method_exists
            && $codebase->methods->existence_provider->has($fq_class_name)
        ) {
            $fake_method_exists = $codebase->methods->existence_provider->doesMethodExist(
                $fq_class_name,
                $method_id->method_name,
                $statements_analyzer,
                null
            ) ?? false;
        }

        $args = $stmt->isFirstClassCallable() ? [] : $stmt->getArgs();

        if (!$naive_method_exists
            && $class_storage->mixin_declaring_fqcln
            && $class_storage->namedMixins
        ) {
            foreach ($class_storage->namedMixins as $mixin) {
                $new_method_id = new MethodIdentifier(
                    $mixin->value,
                    $method_name_lc
                );

                if ($codebase->methods->methodExists(
                    $new_method_id,
                    $context->calling_method_id,
                    $codebase->collect_locations
                        ? new CodeLocation($statements_analyzer, $stmt_name)
                        : null,
                    !$context->collect_initializations
                    && !$context->collect_mutations
                        ? $statements_analyzer
                        : null,
                    $statements_analyzer->getFilePath(),
                    true,
                    $context->insideUse()
                )) {
                    $mixin_candidates = [];
                    foreach ($class_storage->templatedMixins as $mixin_candidate) {
                        $mixin_candidates[] = clone $mixin_candidate;
                    }

                    foreach ($class_storage->namedMixins as $mixin_candidate) {
                        $mixin_candidates[] = clone $mixin_candidate;
                    }

                    $mixin_candidates_no_generic = array_filter($mixin_candidates, function ($check): bool {
                        return !($check instanceof TGenericObject);
                    });

                    // $mixin_candidates_no_generic will only be empty when there are TGenericObject entries.
                    // In that case, Union will be initialized with an empty array but
                    // replaced with non-empty types in the following loop.
                    /** @psalm-suppress ArgumentTypeCoercion */
                    $mixin_candidate_type = new Union($mixin_candidates_no_generic);

                    foreach ($mixin_candidates as $tGenericMixin) {
                        if (!($tGenericMixin instanceof TGenericObject)) {
                            continue;
                        }

                        $mixin_declaring_class_storage = $codebase->classlike_storage_provider->get(
                            $class_storage->mixin_declaring_fqcln
                        );

                        $new_mixin_candidate_type = AtomicPropertyFetchAnalyzer::localizePropertyType(
                            $codebase,
                            new Union([$lhs_type_part]),
                            $tGenericMixin,
                            $class_storage,
                            $mixin_declaring_class_storage
                        );

                        foreach ($mixin_candidate_type->getAtomicTypes() as $type) {
                            $new_mixin_candidate_type->addType($type);
                        }

                        $mixin_candidate_type = $new_mixin_candidate_type;
                    }

                    $new_lhs_type = TypeExpander::expandUnion(
                        $codebase,
                        $mixin_candidate_type,
                        $fq_class_name,
                        $fq_class_name,
                        $class_storage->parent_class,
                        true,
                        false,
                        $class_storage->final
                    );

                    $mixin_context = clone $context;
                    $mixin_context->vars_in_scope['$__tmp_mixin_var__'] = $new_lhs_type;

                    return self::forwardCallToInstanceMethod(
                        $statements_analyzer,
                        $stmt,
                        $stmt_name,
                        $mixin_context,
                        '__tmp_mixin_var__',
                        true
                    );
                }
            }
        }

        $config = $codebase->config;

        $found_method_and_class_storage = self::findPseudoMethodAndClassStorages(
            $codebase,
            $class_storage,
            $method_name_lc
        );

        if ($stmt->isFirstClassCallable()) {
            if ($found_method_and_class_storage) {
                [ $method_storage ] = $found_method_and_class_storage;

                $return_type_candidate = new Union([new TClosure(
                    'Closure',
                    $method_storage->params,
                    $method_storage->return_type,
                    $method_storage->pure
                )]);
            } else {
                $method_exists = $naive_method_exists
                    || $fake_method_exists
                    || isset($class_storage->methods[$method_name_lc])
                    || isset($class_storage->pseudo_static_methods[$method_name_lc]);

                if ($method_exists) {
                    $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id) ?? $method_id;

                    $return_type_candidate = new Union([new TClosure(
                        'Closure',
                        array_values($codebase->getMethodParams($method_id)),
                        $codebase->getMethodReturnType($method_id, $fq_class_name),
                        $codebase->methods->getStorage($declaring_method_id)->pure
                    )]);
                } else {
                    // FIXME: perhaps Psalm should complain about nonexisting method here, or throw a logic exception?
                    $return_type_candidate = Type::getClosure();
                }
            }

            $expanded_return_type = TypeExpander::expandUnion(
                $codebase,
                $return_type_candidate,
                $context->self,
                $class_storage->name,
                $context->parent,
                true,
                false,
                true
            );

            $statements_analyzer->node_data->setType($stmt, $expanded_return_type);

            return true;
        }

        if (!$naive_method_exists
            || !MethodAnalyzer::isMethodVisible(
                $method_id,
                $context,
                $statements_analyzer->getSource()
            )
            || $fake_method_exists
            || ($found_method_and_class_storage
                && ($config->use_phpdoc_method_without_magic_or_parent || $class_storage->parent_class))
        ) {
            $callstatic_id = new MethodIdentifier(
                $fq_class_name,
                '__callstatic'
            );

            if ($codebase->methods->methodExists(
                $callstatic_id,
                $context->calling_method_id,
                $codebase->collect_locations
                    ? new CodeLocation($statements_analyzer, $stmt_name)
                    : null,
                !$context->collect_initializations
                    && !$context->collect_mutations
                    ? $statements_analyzer
                    : null,
                $statements_analyzer->getFilePath(),
                true,
                $context->insideUse()
            )) {
                $callstatic_appearing_id = $codebase->methods->getAppearingMethodId($callstatic_id);
                assert($callstatic_appearing_id !== null);
                $callstatic_pure = false;
                $callstatic_mutation_free = false;
                if ($codebase->methods->hasStorage($callstatic_appearing_id)) {
                    $callstatic_storage = $codebase->methods->getStorage($callstatic_appearing_id);
                    $callstatic_pure = $callstatic_storage->pure;
                    $callstatic_mutation_free = $callstatic_storage->mutation_free;
                }
                if ($codebase->methods->return_type_provider->has($fq_class_name)) {
                    $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                        $statements_analyzer,
                        $method_id->fq_class_name,
                        $method_id->method_name,
                        $stmt,
                        $context,
                        new CodeLocation($statements_analyzer->getSource(), $stmt_name),
                        null,
                        null,
                        strtolower($stmt_name->name)
                    );

                    if ($return_type_candidate) {
                        CallAnalyzer::checkMethodArgs(
                            $method_id,
                            $stmt->getArgs(),
                            null,
                            $context,
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $statements_analyzer
                        );

                        $statements_analyzer->node_data->setType($stmt, $return_type_candidate);

                        return true;
                    }
                }

                if ($found_method_and_class_storage) {
                    [$pseudo_method_storage, $defining_class_storage] = $found_method_and_class_storage;

                    if (self::checkPseudoMethod(
                        $statements_analyzer,
                        $stmt,
                        $method_id,
                        $fq_class_name,
                        $args,
                        $defining_class_storage,
                        $pseudo_method_storage,
                        $context
                    ) === false
                    ) {
                        return false;
                    }

                    if (!$context->inside_throw) {
                        if ($context->pure && !$callstatic_pure) {
                            IssueBuffer::maybeAdd(
                                new ImpureMethodCall(
                                    'Cannot call an impure method from a pure context',
                                    new CodeLocation($statements_analyzer, $stmt_name)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            );
                        } elseif ($context->mutation_free && !$callstatic_mutation_free) {
                            IssueBuffer::maybeAdd(
                                new ImpureMethodCall(
                                    'Cannot call a possibly-mutating method from a mutation-free context',
                                    new CodeLocation($statements_analyzer, $stmt_name)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            );
                        } elseif ($statements_analyzer->getSource()
                            instanceof FunctionLikeAnalyzer
                            && $statements_analyzer->getSource()->track_mutations
                            && !$callstatic_pure
                        ) {
                            if (!$callstatic_mutation_free) {
                                $statements_analyzer->getSource()->inferred_has_mutation = true;
                            }

                            $statements_analyzer->getSource()->inferred_impure = true;
                        }
                    }

                    if ($pseudo_method_storage->return_type) {
                        return true;
                    }
                } else {
                    if (ArgumentsAnalyzer::analyze(
                        $statements_analyzer,
                        $args,
                        null,
                        null,
                        true,
                        $context
                    ) === false) {
                        return false;
                    }
                }

                $array_values = array_map(
                    function (PhpParser\Node\Arg $arg): PhpParser\Node\Expr\ArrayItem {
                        return new VirtualArrayItem(
                            $arg->value,
                            null,
                            false,
                            $arg->getAttributes()
                        );
                    },
                    $args
                );

                $args = [
                    new VirtualArg(
                        new VirtualString((string) $method_id, $stmt_name->getAttributes()),
                        false,
                        false,
                        $stmt_name->getAttributes()
                    ),
                    new VirtualArg(
                        new VirtualArray($array_values, $stmt->getAttributes()),
                        false,
                        false,
                        $stmt->getAttributes()
                    ),
                ];

                $method_id = new MethodIdentifier(
                    $fq_class_name,
                    '__callstatic'
                );
            } elseif ($found_method_and_class_storage
                && ($config->use_phpdoc_method_without_magic_or_parent || $class_storage->parent_class)
            ) {
                [$pseudo_method_storage, $defining_class_storage] = $found_method_and_class_storage;

                if (self::checkPseudoMethod(
                    $statements_analyzer,
                    $stmt,
                    $method_id,
                    $fq_class_name,
                    $args,
                    $defining_class_storage,
                    $pseudo_method_storage,
                    $context
                ) === false
                ) {
                    return false;
                }

                if ($pseudo_method_storage->return_type) {
                    return true;
                }
            } elseif ($stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts[0] === 'parent'
                && !$codebase->methodExists($method_id)
                && !$statements_analyzer->isStatic()
            ) {
                // In case of parent::xxx() call on instance method context (i.e. not static context)
                // with nonexistent method, we try to forward to instance method call for resolve pseudo method.

                // Use parent type as static type for the method call
                $tmp_context = clone $context;
                $tmp_context->vars_in_scope['$__tmp_parent_var__'] = new Union([$lhs_type_part]);

                if (self::forwardCallToInstanceMethod(
                    $statements_analyzer,
                    $stmt,
                    $stmt_name,
                    $tmp_context,
                    '__tmp_parent_var__'
                ) === false) {
                    return false;
                }

                unset($tmp_context);

                // Resolve actual static return type according to caller (i.e. $this) static type
                if (isset($context->vars_in_scope['$this'])
                    && $method_call_type = $statements_analyzer->node_data->getType($stmt)
                ) {
                    $method_call_type = clone $method_call_type;

                    foreach ($method_call_type->getAtomicTypes() as $name => $type) {
                        if ($type instanceof TNamedObject && $type->was_static && $type->value === $fq_class_name) {
                            // Replace parent&static type to actual static type
                            $method_call_type->removeType($name);
                            $method_call_type->addType($context->vars_in_scope['$this']->getSingleAtomic());
                        }
                    }

                    $statements_analyzer->node_data->setType($stmt, $method_call_type);
                }

                return true;
            }

            if (!$context->check_methods) {
                if (ArgumentsAnalyzer::analyze(
                    $statements_analyzer,
                    $stmt->getArgs(),
                    null,
                    null,
                    true,
                    $context
                ) === false) {
                    return false;
                }

                return true;
            }
        }

        $does_method_exist = MethodAnalyzer::checkMethodExists(
            $codebase,
            $method_id,
            new CodeLocation($statements_analyzer, $stmt),
            $statements_analyzer->getSuppressedIssues(),
            $context->calling_method_id
        );

        if (!$does_method_exist) {
            if (ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                null,
                null,
                true,
                $context
            ) === false) {
                return false;
            }

            if ($codebase->alter_code && $fq_class_name && !$moved_call) {
                $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $statements_analyzer,
                    $stmt->class,
                    $fq_class_name,
                    $context->calling_method_id
                );
            }

            return true;
        }

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        if ($class_storage->deprecated && $fq_class_name !== $context->self) {
            IssueBuffer::maybeAdd(
                new DeprecatedClass(
                    $fq_class_name . ' is marked deprecated',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $fq_class_name
                ),
                $statements_analyzer->getSuppressedIssues()
            );
        }

        if ($context->self && ! NamespaceAnalyzer::isWithinAny($context->self, $class_storage->internal)) {
            IssueBuffer::maybeAdd(
                new InternalClass(
                    $fq_class_name . ' is internal to ' . InternalClass::listToPhrase($class_storage->internal)
                        . ' but called from ' . $context->self,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $fq_class_name
                ),
                $statements_analyzer->getSuppressedIssues()
            );
        }

        if (MethodVisibilityAnalyzer::analyze(
            $method_id,
            $context,
            $statements_analyzer->getSource(),
            new CodeLocation($statements_analyzer, $stmt),
            $statements_analyzer->getSuppressedIssues()
        ) === false) {
            return false;
        }

        if ((!$stmt->class instanceof PhpParser\Node\Name
                || $stmt->class->parts[0] !== 'parent'
                || $statements_analyzer->isStatic())
            && (
                !$context->self
                || $statements_analyzer->isStatic()
                || !$codebase->classExtends($context->self, $fq_class_name)
            )
        ) {
            if (MethodAnalyzer::checkStatic(
                $method_id,
                ($stmt->class instanceof PhpParser\Node\Name
                    && strtolower($stmt->class->parts[0]) === 'self')
                    || $context->self === $fq_class_name,
                !$statements_analyzer->isStatic(),
                $codebase,
                new CodeLocation($statements_analyzer, $stmt),
                $statements_analyzer->getSuppressedIssues(),
                $is_dynamic_this_method
            ) === false) {
                // fall through
            }

            if ($is_dynamic_this_method) {
                return self::forwardCallToInstanceMethod(
                    $statements_analyzer,
                    $stmt,
                    $stmt_name,
                    $context
                );
            }
        }

        $has_existing_method = true;

        ExistingAtomicStaticCallAnalyzer::analyze(
            $statements_analyzer,
            $stmt,
            $stmt_name,
            $args,
            $context,
            $lhs_type_part,
            $method_id,
            $cased_method_id,
            $class_storage,
            $moved_call
        );

        return true;
    }

    /**
     * @param  list<PhpParser\Node\Arg> $args
     * @return false|null
     */
    private static function checkPseudoMethod(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $stmt,
        MethodIdentifier $method_id,
        string $static_fq_class_name,
        array $args,
        ClassLikeStorage $class_storage,
        MethodStorage $pseudo_method_storage,
        Context $context
    ): ?bool {
        if (ArgumentsAnalyzer::analyze(
            $statements_analyzer,
            $args,
            $pseudo_method_storage->params,
            (string) $method_id,
            true,
            $context
        ) === false) {
            return false;
        }

        $codebase = $statements_analyzer->getCodebase();

        if (ArgumentsAnalyzer::checkArgumentsMatch(
            $statements_analyzer,
            $args,
            $method_id,
            $pseudo_method_storage->params,
            $pseudo_method_storage,
            null,
            null,
            new CodeLocation($statements_analyzer, $stmt),
            $context
        ) === false) {
            return false;
        }

        $method_storage = null;

        if ($statements_analyzer->data_flow_graph) {
            try {
                $method_storage = $codebase->methods->getStorage($method_id);

                ArgumentsAnalyzer::analyze(
                    $statements_analyzer,
                    $args,
                    $method_storage->params,
                    (string) $method_id,
                    true,
                    $context
                );

                ArgumentsAnalyzer::checkArgumentsMatch(
                    $statements_analyzer,
                    $args,
                    $method_id,
                    $method_storage->params,
                    $method_storage,
                    null,
                    null,
                    new CodeLocation($statements_analyzer, $stmt),
                    $context
                );
            } catch (Exception $e) {
                // do nothing
            }
        }

        if ($pseudo_method_storage->return_type) {
            $return_type_candidate = clone $pseudo_method_storage->return_type;

            $return_type_candidate = TypeExpander::expandUnion(
                $statements_analyzer->getCodebase(),
                $return_type_candidate,
                $class_storage->name,
                $static_fq_class_name,
                $class_storage->parent_class
            );

            if ($method_storage) {
                StaticCallAnalyzer::taintReturnType(
                    $statements_analyzer,
                    $stmt,
                    $method_id,
                    (string) $method_id,
                    $return_type_candidate,
                    $method_storage,
                    null,
                    $context
                );
            }

            $stmt_type = $statements_analyzer->node_data->getType($stmt);

            $statements_analyzer->node_data->setType(
                $stmt,
                Type::combineUnionTypes(
                    $return_type_candidate,
                    $stmt_type
                )
            );
        }

        return null;
    }

    public static function handleNonObjectCall(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $stmt,
        Context $context,
        Atomic $lhs_type_part,
        bool $ignore_nullable_issues
    ): void {
        $codebase = $statements_analyzer->getCodebase();
        $config = $codebase->config;

        if ($lhs_type_part instanceof TMixed
            || $lhs_type_part instanceof TTemplateParam
            || $lhs_type_part instanceof TClassString
            || $lhs_type_part instanceof TObject
        ) {
            if ($stmt->name instanceof PhpParser\Node\Identifier) {
                $codebase->analyzer->addMixedMemberName(
                    strtolower($stmt->name->name),
                    $context->calling_method_id ?: $statements_analyzer->getFileName()
                );
            }

            IssueBuffer::maybeAdd(
                new MixedMethodCall(
                    'Cannot call method on an unknown class',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            );

            return;
        }

        if ($lhs_type_part instanceof TString) {
            if ($config->allow_string_standin_for_class
                && !$lhs_type_part instanceof TNumericString
            ) {
                return;
            }

            IssueBuffer::maybeAdd(
                new InvalidStringClass(
                    'String cannot be used as a class',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            );

            return;
        }

        if ($lhs_type_part instanceof TNull
            && $ignore_nullable_issues
        ) {
            return;
        }

        IssueBuffer::maybeAdd(
            new UndefinedClass(
                'Type ' . $lhs_type_part . ' cannot be called as a class',
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                (string) $lhs_type_part
            ),
            $statements_analyzer->getSuppressedIssues()
        );
    }

    /**
     * Try to find matching pseudo method over ancestors (including interfaces).
     *
     * Returns the pseudo method if exists, with its defining class storage.
     * If the method is not declared, null is returned.
     *
     * @param Codebase $codebase
     * @param ClassLikeStorage $static_class_storage The called class
     * @param lowercase-string $method_name_lc
     *
     * @return array{MethodStorage, ClassLikeStorage}|null
     */
    private static function findPseudoMethodAndClassStorages(
        Codebase $codebase,
        ClassLikeStorage $static_class_storage,
        string $method_name_lc
    ): ?array {
        if ($pseudo_method_storage = $static_class_storage->pseudo_static_methods[$method_name_lc] ?? null) {
            return [$pseudo_method_storage, $static_class_storage];
        }

        $ancestors = $static_class_storage->class_implements + $static_class_storage->parent_classes;

        foreach ($ancestors as $fq_class_name => $_) {
            $class_storage = $codebase->classlikes->getStorageFor($fq_class_name);

            if ($class_storage && isset($class_storage->pseudo_static_methods[$method_name_lc])) {
                return [
                    $class_storage->pseudo_static_methods[$method_name_lc],
                    $class_storage
                ];
            }
        }

        return null;
    }

    /**
     * Forward static call to instance call, using `VirtualMethodCall` and `MethodCallAnalyzer::analyze()`
     * The resolved method return type will be set as type of the $stmt node.
     *
     * @param StatementsAnalyzer $statements_analyzer
     * @param PhpParser\Node\Expr\StaticCall $stmt
     * @param PhpParser\Node\Identifier $stmt_name
     * @param Context $context
     * @param string $virtual_var_name Temporary var name to use for create the fake MethodCall statement.
     * @param bool $always_set_node_type If true, when the method has no declared typed, mixed will be set on node.
     *
     * @return bool Result of analysis. False if the call is invalid.
     *
     * @see MethodCallAnalyzer::analyze()
     */
    private static function forwardCallToInstanceMethod(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $stmt,
        PhpParser\Node\Identifier $stmt_name,
        Context $context,
        string $virtual_var_name = 'this',
        bool $always_set_node_type = false
    ): bool {
        $old_data_provider = $statements_analyzer->node_data;

        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        $fake_method_call_expr = new VirtualMethodCall(
            new VirtualVariable($virtual_var_name, $stmt->class->getAttributes()),
            $stmt_name,
            $stmt->getArgs(),
            $stmt->getAttributes()
        );

        if (MethodCallAnalyzer::analyze(
            $statements_analyzer,
            $fake_method_call_expr,
            $context
        ) === false) {
            return false;
        }

        $fake_method_call_type = $statements_analyzer->node_data->getType($fake_method_call_expr);

        $statements_analyzer->node_data = $old_data_provider;

        if ($fake_method_call_type) {
            $statements_analyzer->node_data->setType($stmt, $fake_method_call_type);
        } elseif ($always_set_node_type) {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());
        }

        return true;
    }
}
