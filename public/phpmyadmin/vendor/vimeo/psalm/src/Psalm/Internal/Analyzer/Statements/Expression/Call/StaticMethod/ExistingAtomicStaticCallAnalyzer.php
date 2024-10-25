<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\StaticMethod;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\MethodCallProhibitionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\StaticCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\AbstractMethodCall;
use Psalm\Issue\ImpureMethodCall;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;
use Psalm\Storage\Assertion;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Union;

use function array_map;
use function count;
use function explode;
use function in_array;
use function is_string;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

class ExistingAtomicStaticCallAnalyzer
{
    /**
     * @param  list<PhpParser\Node\Arg> $args
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $stmt,
        PhpParser\Node\Identifier $stmt_name,
        array $args,
        Context $context,
        Atomic $lhs_type_part,
        MethodIdentifier $method_id,
        string $cased_method_id,
        ClassLikeStorage $class_storage,
        bool &$moved_call
    ): void {
        $fq_class_name = $method_id->fq_class_name;
        $method_name_lc = $method_id->method_name;

        $codebase = $statements_analyzer->getCodebase();
        $config = $codebase->config;

        if (MethodCallProhibitionAnalyzer::analyze(
            $codebase,
            $context,
            $method_id,
            $statements_analyzer->getFullyQualifiedFunctionMethodOrNamespaceName(),
            new CodeLocation($statements_analyzer->getSource(), $stmt),
            $statements_analyzer->getSuppressedIssues()
        ) === false) {
            // fall through
        }

        if ($class_storage->user_defined
            && $context->self
            && ($context->collect_mutations || $context->collect_initializations)
        ) {
            $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);

            if (!$appearing_method_id) {
                return;
            }

            $appearing_method_class_name = $appearing_method_id->fq_class_name;

            if ($codebase->classExtends($context->self, $appearing_method_class_name)) {
                $old_context_include_location = $context->include_location;
                $old_self = $context->self;
                $context->include_location = new CodeLocation($statements_analyzer->getSource(), $stmt);
                $context->self = $appearing_method_class_name;

                $file_analyzer = $statements_analyzer->getFileAnalyzer();

                if ($context->collect_mutations) {
                    $file_analyzer->getMethodMutations($appearing_method_id, $context);
                } else {
                    // collecting initializations
                    $local_vars_in_scope = [];
                    $local_vars_possibly_in_scope = [];

                    foreach ($context->vars_in_scope as $var => $_) {
                        if (strpos($var, '$this->') !== 0 && $var !== '$this') {
                            $local_vars_in_scope[$var] = $context->vars_in_scope[$var];
                        }
                    }

                    foreach ($context->vars_possibly_in_scope as $var => $_) {
                        if (strpos($var, '$this->') !== 0 && $var !== '$this') {
                            $local_vars_possibly_in_scope[$var] = $context->vars_possibly_in_scope[$var];
                        }
                    }

                    if (!isset($context->initialized_methods[(string) $appearing_method_id])) {
                        if ($context->initialized_methods === null) {
                            $context->initialized_methods = [];
                        }

                        $context->initialized_methods[(string) $appearing_method_id] = true;

                        $file_analyzer->getMethodMutations($appearing_method_id, $context);

                        foreach ($local_vars_in_scope as $var => $type) {
                            $context->vars_in_scope[$var] = $type;
                        }

                        foreach ($local_vars_possibly_in_scope as $var => $type) {
                            $context->vars_possibly_in_scope[$var] = $type;
                        }
                    }
                }

                $context->include_location = $old_context_include_location;
                $context->self = $old_self;
            }
        }

        $found_generic_params = ClassTemplateParamCollector::collect(
            $codebase,
            $class_storage,
            $class_storage,
            $method_name_lc,
            $lhs_type_part,
            !$statements_analyzer->isStatic() && $method_id->fq_class_name === $context->self
        );

        if ($found_generic_params
            && $stmt->class instanceof PhpParser\Node\Name
            && $stmt->class->parts === ['parent']
            && $context->self
            && ($self_class_storage = $codebase->classlike_storage_provider->get($context->self))
            && $self_class_storage->template_extended_params
        ) {
            foreach ($self_class_storage->template_extended_params as $template_fq_class_name => $extended_types) {
                foreach ($extended_types as $type_key => $extended_type) {
                    if (isset($found_generic_params[$type_key][$template_fq_class_name])) {
                        $found_generic_params[$type_key][$template_fq_class_name] = clone $extended_type;
                        continue;
                    }

                    foreach ($extended_type->getAtomicTypes() as $t) {
                        if ($t instanceof TTemplateParam
                            && isset($found_generic_params[$t->param_name][$t->defining_class])
                        ) {
                            $found_generic_params[$type_key][$template_fq_class_name]
                                = $found_generic_params[$t->param_name][$t->defining_class];
                        } else {
                            $found_generic_params[$type_key][$template_fq_class_name]
                                = clone $extended_type;
                            break;
                        }
                    }
                }
            }
        }

        $template_result = new TemplateResult([], $found_generic_params ?: []);

        if (CallAnalyzer::checkMethodArgs(
            $method_id,
            $args,
            $template_result,
            $context,
            new CodeLocation($statements_analyzer->getSource(), $stmt),
            $statements_analyzer
        ) === false) {
            return;
        }

        $fq_class_name = $stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts === ['parent']
            ? (string) $statements_analyzer->getFQCLN()
            : $fq_class_name;

        $self_fq_class_name = $fq_class_name;

        $return_type_candidate = null;

        if ($codebase->methods->return_type_provider->has($fq_class_name)) {
            $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                $statements_analyzer,
                $fq_class_name,
                $stmt_name->name,
                $stmt,
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt_name)
            );
        }

        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

        if (!$return_type_candidate
            && $declaring_method_id
            && (string) $declaring_method_id !== (string) $method_id
        ) {
            $declaring_fq_class_name = $declaring_method_id->fq_class_name;
            $declaring_method_name = $declaring_method_id->method_name;

            if ($codebase->methods->return_type_provider->has($declaring_fq_class_name)) {
                $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                    $statements_analyzer,
                    $declaring_fq_class_name,
                    $declaring_method_name,
                    $stmt,
                    $context,
                    new CodeLocation($statements_analyzer->getSource(), $stmt_name),
                    null,
                    $fq_class_name,
                    $stmt_name->name
                );
            }
        }

        if (!$return_type_candidate) {
            $return_type_candidate = self::getMethodReturnType(
                $statements_analyzer,
                $codebase,
                $stmt,
                $method_id,
                $args,
                $template_result,
                $self_fq_class_name,
                $lhs_type_part,
                $context,
                $fq_class_name,
                $class_storage,
                $config
            );
        }

        $method_storage = $codebase->methods->getUserMethodStorage($method_id);

        if ($method_storage) {
            if ($method_storage->abstract
                && $stmt->class instanceof PhpParser\Node\Name
                && (!$context->self
                    || !UnionTypeComparator::isContainedBy(
                        $codebase,
                        $context->vars_in_scope['$this']
                            ?? new Union([
                                new TNamedObject($context->self)
                            ]),
                        new Union([
                            new TNamedObject($method_id->fq_class_name)
                        ])
                    ))
            ) {
                IssueBuffer::maybeAdd(
                    new AbstractMethodCall(
                        'Cannot call an abstract static method ' . $method_id . ' directly',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }

            if (!$context->inside_throw) {
                if ($context->pure && !$method_storage->pure) {
                    IssueBuffer::maybeAdd(
                        new ImpureMethodCall(
                            'Cannot call an impure method from a pure context',
                            new CodeLocation($statements_analyzer, $stmt_name)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                } elseif ($context->mutation_free && !$method_storage->mutation_free) {
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
                    && !$method_storage->pure
                ) {
                    if (!$method_storage->mutation_free) {
                        $statements_analyzer->getSource()->inferred_has_mutation = true;
                    }

                    $statements_analyzer->getSource()->inferred_impure = true;
                }
            }

            $generic_params = $template_result->lower_bounds;

            if ($method_storage->assertions) {
                CallAnalyzer::applyAssertionsToContext(
                    $stmt_name,
                    null,
                    $method_storage->assertions,
                    $stmt->getArgs(),
                    $generic_params,
                    $context,
                    $statements_analyzer
                );
            }

            if ($method_storage->if_true_assertions) {
                $statements_analyzer->node_data->setIfTrueAssertions(
                    $stmt,
                    array_map(
                        function (Assertion $assertion) use ($generic_params, $codebase): Assertion {
                            return $assertion->getUntemplatedCopy($generic_params, null, $codebase);
                        },
                        $method_storage->if_true_assertions
                    )
                );
            }

            if ($method_storage->if_false_assertions) {
                $statements_analyzer->node_data->setIfFalseAssertions(
                    $stmt,
                    array_map(
                        function (Assertion $assertion) use ($generic_params, $codebase): Assertion {
                            return $assertion->getUntemplatedCopy($generic_params, null, $codebase);
                        },
                        $method_storage->if_false_assertions
                    )
                );
            }
        }

        if ($codebase->alter_code) {
            foreach ($codebase->call_transforms as $original_pattern => $transformation) {
                if ($declaring_method_id
                    && strtolower((string) $declaring_method_id) . '\((.*\))' === $original_pattern
                ) {
                    if (strpos($transformation, '($1)') === strlen($transformation) - 4
                        && $stmt->class instanceof PhpParser\Node\Name
                    ) {
                        $new_method_id = substr($transformation, 0, -4);
                        $old_declaring_fq_class_name = $declaring_method_id->fq_class_name;
                        [$new_fq_class_name, $new_method_name] = explode('::', $new_method_id);

                        if ($codebase->classlikes->handleClassLikeReferenceInMigration(
                            $codebase,
                            $statements_analyzer,
                            $stmt->class,
                            $new_fq_class_name,
                            $context->calling_method_id,
                            strtolower($old_declaring_fq_class_name) !== strtolower($new_fq_class_name),
                            $stmt->class->parts[0] === 'self'
                        )) {
                            $moved_call = true;
                        }

                        $file_manipulations = [];

                        $file_manipulations[] = new FileManipulation(
                            (int) $stmt_name->getAttribute('startFilePos'),
                            (int) $stmt_name->getAttribute('endFilePos') + 1,
                            $new_method_name
                        );

                        FileManipulationBuffer::add(
                            $statements_analyzer->getFilePath(),
                            $file_manipulations
                        );
                    }
                }
            }
        }

        if ($config->eventDispatcher->hasAfterMethodCallAnalysisHandlers()) {
            $file_manipulations = [];

            $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);

            if ($appearing_method_id !== null && $declaring_method_id) {
                $event = new AfterMethodCallAnalysisEvent(
                    $stmt,
                    (string) $method_id,
                    (string) $appearing_method_id,
                    (string) $declaring_method_id,
                    $context,
                    $statements_analyzer,
                    $codebase,
                    $file_manipulations,
                    $return_type_candidate
                );
                $config->eventDispatcher->dispatchAfterMethodCallAnalysis($event);
                $file_manipulations = $event->getFileReplacements();
                $return_type_candidate = $event->getReturnTypeCandidate();
            }

            if ($file_manipulations) {
                FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
            }
        }

        $return_type_candidate = $return_type_candidate ?? Type::getMixed();

        StaticCallAnalyzer::taintReturnType(
            $statements_analyzer,
            $stmt,
            $method_id,
            $cased_method_id,
            $return_type_candidate,
            $method_storage,
            $template_result,
            $context
        );

        $stmt_type = $statements_analyzer->node_data->getType($stmt);
        $statements_analyzer->node_data->setType(
            $stmt,
            Type::combineUnionTypes($stmt_type, $return_type_candidate)
        );

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            $codebase->analyzer->addNodeReference(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                $method_id . '()'
            );

            if ($stmt_type = $statements_analyzer->node_data->getType($stmt)) {
                $codebase->analyzer->addNodeType(
                    $statements_analyzer->getFilePath(),
                    $stmt->name,
                    $stmt_type->getId(),
                    $stmt
                );
            }
        }
    }

    /**
     * @param list<PhpParser\Node\Arg> $args
     */
    private static function getMethodReturnType(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\StaticCall $stmt,
        MethodIdentifier $method_id,
        array $args,
        TemplateResult $template_result,
        ?string &$self_fq_class_name,
        Atomic $lhs_type_part,
        Context $context,
        string $fq_class_name,
        ClassLikeStorage $class_storage,
        Config $config
    ): ?Union {
        $return_type_candidate = $codebase->methods->getMethodReturnType(
            $method_id,
            $self_fq_class_name,
            $statements_analyzer,
            $args
        );

        if ($return_type_candidate) {
            $return_type_candidate = clone $return_type_candidate;

            if ($template_result->template_types) {
                $bindable_template_types = $return_type_candidate->getTemplateTypes();

                foreach ($bindable_template_types as $template_type) {
                    if (!isset(
                        $template_result->lower_bounds
                        [$template_type->param_name]
                        [$template_type->defining_class]
                    )) {
                        if ($template_type->param_name === 'TFunctionArgCount') {
                            $template_result->lower_bounds[$template_type->param_name] = [
                                'fn-' . strtolower((string)$method_id) => [
                                    new TemplateBound(
                                        Type::getInt(false, count($stmt->getArgs()))
                                    )
                                ]
                            ];
                        } elseif ($template_type->param_name === 'TPhpMajorVersion') {
                            $template_result->lower_bounds[$template_type->param_name] = [
                                'fn-' . strtolower((string)$method_id) => [
                                    new TemplateBound(
                                        Type::getInt(false, $codebase->php_major_version)
                                    )
                                ]
                            ];
                        } elseif ($template_type->param_name === 'TPhpVersionId') {
                            $template_result->lower_bounds[$template_type->param_name] = [
                                'fn-' . strtolower((string) $method_id) => [
                                    new TemplateBound(
                                        Type::getInt(
                                            false,
                                            10000 * $codebase->php_major_version
                                            + 100 * $codebase->php_minor_version
                                        )
                                    )
                                ]
                            ];
                        } else {
                            $template_result->lower_bounds[$template_type->param_name] = [
                                ($template_type->defining_class) => [
                                    new TemplateBound(Type::getEmpty())
                                ]
                            ];
                        }
                    }
                }
            }

            $context_final = false;

            if ($lhs_type_part instanceof TTemplateParam) {
                $static_type = $lhs_type_part;
            } elseif ($lhs_type_part instanceof TTemplateParamClass) {
                $static_type = new TTemplateParam(
                    $lhs_type_part->param_name,
                    $lhs_type_part->as_type
                        ? new Union([$lhs_type_part->as_type])
                        : Type::getObject(),
                    $lhs_type_part->defining_class
                );
            } elseif ($stmt->class instanceof PhpParser\Node\Name
                && count($stmt->class->parts) === 1
                && in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)
                && $lhs_type_part instanceof TNamedObject
                && $context->self
            ) {
                $static_type = $context->self;
                $context_final = $codebase->classlike_storage_provider->get($context->self)->final;
            } elseif ($context->calling_method_id !== null) {
                // differentiate between these cases:
                //   1. "static" comes from the CALLED static method - use $fq_class_name.
                //   2. "static" in return type comes from return type of the
                //   method CALLING the currently analyzed static method - use $context->self.
                $static_type = self::hasStaticInType($return_type_candidate)
                    ? $fq_class_name
                    : $context->self;
            } else {
                $static_type = $fq_class_name;
            }

            if ($template_result->lower_bounds) {
                $return_type_candidate = TypeExpander::expandUnion(
                    $codebase,
                    $return_type_candidate,
                    null,
                    null,
                    null
                );

                TemplateInferredTypeReplacer::replace(
                    $return_type_candidate,
                    $template_result,
                    $codebase
                );
            }

            $return_type_candidate = TypeExpander::expandUnion(
                $codebase,
                $return_type_candidate,
                $self_fq_class_name,
                $static_type,
                $class_storage->parent_class,
                true,
                false,
                is_string($static_type)
                && ($static_type !== $context->self
                    || $class_storage->final
                    || $context_final)
            );

            $secondary_return_type_location = null;

            $return_type_location = $codebase->methods->getMethodReturnTypeLocation(
                $method_id,
                $secondary_return_type_location
            );

            if ($secondary_return_type_location) {
                $return_type_location = $secondary_return_type_location;
            }

            // only check the type locally if it's defined externally
            if ($return_type_location && !$config->isInProjectDirs($return_type_location->file_path)) {
                $return_type_candidate->check(
                    $statements_analyzer,
                    new CodeLocation($statements_analyzer, $stmt),
                    $statements_analyzer->getSuppressedIssues(),
                    $context->phantom_classes,
                    true,
                    false,
                    false,
                    $context->calling_method_id
                );
            }
        }

        return $return_type_candidate;
    }

    /**
     * Dumb way to determine whether a type contains "static" somewhere inside.
     */
    private static function hasStaticInType(Type\TypeNode $type): bool
    {
        if ($type instanceof TNamedObject && ($type->value === 'static' || $type->was_static)) {
            return true;
        }

        foreach ($type->getChildNodes() as $child_type) {
            if (self::hasStaticInType($child_type)) {
                return true;
            }
        }

        return false;
    }
}
