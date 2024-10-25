<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentMapPopulator;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Analyzer\Statements\Expression\Call\FunctionCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\IfThisIsMismatch;
use Psalm\Issue\InvalidPropertyAssignmentValue;
use Psalm\Issue\MixedPropertyTypeCoercion;
use Psalm\Issue\PossiblyInvalidPropertyAssignmentValue;
use Psalm\Issue\PropertyTypeCoercion;
use Psalm\Issue\UndefinedThisPropertyAssignment;
use Psalm\Issue\UndefinedThisPropertyFetch;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\VirtualFuncCall;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;
use Psalm\Storage\Assertion;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_map;
use function count;
use function explode;
use function in_array;
use function strtolower;

class ExistingAtomicMethodCallAnalyzer extends CallAnalyzer
{
    /**
     * @param  TNamedObject|TTemplateParam|null  $static_type
     * @param  list<PhpParser\Node\Arg> $args
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        PhpParser\Node\Identifier $stmt_name,
        array $args,
        Codebase $codebase,
        Context $context,
        TNamedObject $lhs_type_part,
        ?Atomic $static_type,
        ?string $lhs_var_id,
        MethodIdentifier $method_id,
        AtomicMethodCallAnalysisResult $result
    ): Union {
        $config = $codebase->config;

        $fq_class_name = $lhs_type_part->value;

        if ($fq_class_name === 'static') {
            $fq_class_name = (string) $context->self;
        }

        $method_name_lc = $method_id->method_name;

        $cased_method_id = $fq_class_name . '::' . $stmt_name->name;

        $result->existent_method_ids[] = $method_id->__toString();

        if ($context->collect_initializations && $context->calling_method_id) {
            [$calling_method_class] = explode('::', $context->calling_method_id);
            $codebase->file_reference_provider->addMethodReferenceToClassMember(
                $calling_method_class . '::__construct',
                strtolower((string) $method_id),
                false
            );
        }

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

        if ($fq_class_name === 'Closure' && $method_name_lc === '__invoke') {
            $statements_analyzer->node_data = clone $statements_analyzer->node_data;

            $fake_function_call = new VirtualFuncCall(
                $stmt->var,
                $args,
                $stmt->getAttributes()
            );

            FunctionCallAnalyzer::analyze(
                $statements_analyzer,
                $fake_function_call,
                $context
            );

            return $statements_analyzer->node_data->getType($fake_function_call) ?? Type::getMixed();
        }

        $source = $statements_analyzer->getSource();

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            $codebase->analyzer->addNodeReference(
                $statements_analyzer->getFilePath(),
                $stmt_name,
                $method_id . '()'
            );
        }

        if ($context->collect_initializations && $context->calling_method_id) {
            [$calling_method_class] = explode('::', $context->calling_method_id);
            $codebase->file_reference_provider->addMethodReferenceToClassMember(
                $calling_method_class . '::__construct',
                strtolower((string) $method_id),
                false
            );
        }

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable
            && ($context->collect_initializations || $context->collect_mutations)
            && $stmt->var->name === 'this'
            && $source instanceof FunctionLikeAnalyzer
        ) {
            self::collectSpecialInformation($source, $stmt_name->name, $context);
        }

        $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $parent_source = $statements_analyzer->getSource();

        $class_template_params = ClassTemplateParamCollector::collect(
            $codebase,
            $codebase->methods->getClassLikeStorageForMethod($method_id),
            $class_storage,
            $method_name_lc,
            $lhs_type_part,
            $lhs_var_id === '$this'
        );

        if ($lhs_var_id === '$this' && $parent_source instanceof FunctionLikeAnalyzer) {
            $grandparent_source = $parent_source->getSource();

            if ($grandparent_source instanceof TraitAnalyzer) {
                $fq_trait_name = $grandparent_source->getFQCLN();

                $fq_trait_name_lc = strtolower($fq_trait_name);

                $trait_storage = $codebase->classlike_storage_provider->get($fq_trait_name_lc);

                if (isset($trait_storage->methods[$method_name_lc])) {
                    $trait_method_id = new MethodIdentifier($trait_storage->name, $method_name_lc);

                    $class_template_params = ClassTemplateParamCollector::collect(
                        $codebase,
                        $codebase->methods->getClassLikeStorageForMethod($trait_method_id),
                        $class_storage,
                        $method_name_lc,
                        $lhs_type_part,
                        true
                    );
                }
            }
        }

        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

        try {
            $method_storage = $codebase->methods->getStorage($declaring_method_id ?? $method_id);
        } catch (UnexpectedValueException $e) {
            $method_storage = null;
        }

        $method_template_params = [];

        if ($method_storage && $method_storage->if_this_is_type) {
            $method_template_result = new TemplateResult($method_storage->template_types ?: [], []);

            TemplateStandinTypeReplacer::replace(
                clone $method_storage->if_this_is_type,
                $method_template_result,
                $codebase,
                null,
                new Union([$lhs_type_part])
            );

            $method_template_params = $method_template_result->lower_bounds;
        }

        $template_result = new TemplateResult([], $class_template_params ?: []);
        $template_result->lower_bounds += $method_template_params;

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

        $is_first_class_callable = $stmt->isFirstClassCallable();

        if (!$is_first_class_callable && self::checkMethodArgs(
            $method_id,
            $args,
            $template_result,
            $context,
            new CodeLocation($source, $stmt_name),
            $statements_analyzer
        ) === false) {
            return Type::getMixed();
        }

        $return_type_candidate = MethodCallReturnTypeFetcher::fetch(
            $statements_analyzer,
            $codebase,
            $stmt,
            $context,
            $method_id,
            $declaring_method_id,
            $method_id,
            $cased_method_id,
            $lhs_type_part,
            $static_type,
            $args,
            $result,
            $template_result
        );

        if ($is_first_class_callable) {
            return $return_type_candidate;
        }

        $in_call_map = InternalCallMapHandler::inCallMap((string) ($declaring_method_id ?? $method_id));

        if (!$in_call_map) {
            $name_code_location = new CodeLocation($statements_analyzer, $stmt_name);

            MethodCallProhibitionAnalyzer::analyze(
                $codebase,
                $context,
                $method_id,
                $statements_analyzer->getFullyQualifiedFunctionMethodOrNamespaceName(),
                $name_code_location,
                $statements_analyzer->getSuppressedIssues()
            );

            $getter_return_type = self::getMagicGetterOrSetterProperty(
                $statements_analyzer,
                $stmt,
                $stmt_name,
                $context,
                $fq_class_name
            );

            if ($getter_return_type) {
                $return_type_candidate = $getter_return_type;
            }
        }

        if ($method_storage) {
            if ($method_storage->if_this_is_type) {
                $class_type = new Union([$lhs_type_part]);
                $if_this_is_type = clone $method_storage->if_this_is_type;

                TemplateInferredTypeReplacer::replace($if_this_is_type, $template_result, $codebase);

                if (!UnionTypeComparator::isContainedBy($codebase, $class_type, $if_this_is_type)) {
                    IssueBuffer::maybeAdd(
                        new IfThisIsMismatch(
                            'Class type must be ' . $method_storage->if_this_is_type->getId()
                            . ' current type ' . $class_type->getId(),
                            new CodeLocation($source, $stmt->name)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                }
            }

            if ($method_storage->self_out_type && $lhs_var_id) {
                $self_out_candidate = clone $method_storage->self_out_type;

                if ($template_result->lower_bounds) {
                    $self_out_candidate = TypeExpander::expandUnion(
                        $codebase,
                        $self_out_candidate,
                        $fq_class_name,
                        null,
                        $class_storage->parent_class,
                        true,
                        false,
                        $static_type instanceof TNamedObject
                            && $codebase->classlike_storage_provider->get($static_type->value)->final,
                        true
                    );
                }

                $self_out_candidate = MethodCallReturnTypeFetcher::replaceTemplateTypes(
                    $self_out_candidate,
                    $template_result,
                    $method_id,
                    count($args),
                    $codebase
                );

                $self_out_candidate = TypeExpander::expandUnion(
                    $codebase,
                    $self_out_candidate,
                    $fq_class_name,
                    $static_type,
                    $class_storage->parent_class,
                    true,
                    false,
                    $static_type instanceof TNamedObject
                        && $codebase->classlike_storage_provider->get($static_type->value)->final,
                    true
                );

                $context->vars_in_scope[$lhs_var_id] = $self_out_candidate;
            }

            if (!$context->collect_mutations && !$context->collect_initializations) {
                MethodCallPurityAnalyzer::analyze(
                    $statements_analyzer,
                    $codebase,
                    $stmt,
                    $lhs_var_id,
                    $cased_method_id,
                    $method_id,
                    $method_storage,
                    $class_storage,
                    $context,
                    $config,
                    $result
                );
            }

            $has_packed_arg = false;
            foreach ($args as $arg) {
                $has_packed_arg = $has_packed_arg || $arg->unpack;
            }

            if (!$has_packed_arg) {
                $has_variadic_param = $method_storage->variadic;

                foreach ($method_storage->params as $param) {
                    $has_variadic_param = $has_variadic_param || $param->is_variadic;
                }

                for ($i = count($args), $j = count($method_storage->params); $i < $j; ++$i) {
                    $param = $method_storage->params[$i];

                    if (!$param->is_optional
                        && !$param->is_variadic
                        && !$in_call_map
                    ) {
                        $result->too_few_arguments = true;
                        $result->too_few_arguments_method_ids[] = $declaring_method_id ?? $method_id;
                    }
                }

                if ($has_variadic_param || count($method_storage->params) >= count($args) || $in_call_map) {
                    $result->too_many_arguments = false;
                } else {
                    $result->too_many_arguments_method_ids[] = $declaring_method_id ?? $method_id;
                }
            }

            $class_template_params = $template_result->lower_bounds;

            if ($method_storage->assertions) {
                self::applyAssertionsToContext(
                    $stmt_name,
                    ExpressionIdentifier::getArrayVarId($stmt->var, null, $statements_analyzer),
                    $method_storage->assertions,
                    $args,
                    $class_template_params,
                    $context,
                    $statements_analyzer
                );
            }

            if ($method_storage->if_true_assertions) {
                $statements_analyzer->node_data->setIfTrueAssertions(
                    $stmt,
                    array_map(
                        function (Assertion $assertion) use (
                            $class_template_params,
                            $lhs_var_id,
                            $codebase
                        ): Assertion {
                            return $assertion->getUntemplatedCopy(
                                $class_template_params ?: [],
                                $lhs_var_id,
                                $codebase
                            );
                        },
                        $method_storage->if_true_assertions
                    )
                );
            }

            if ($method_storage->if_false_assertions) {
                $statements_analyzer->node_data->setIfFalseAssertions(
                    $stmt,
                    array_map(
                        function (Assertion $assertion) use (
                            $class_template_params,
                            $lhs_var_id,
                            $codebase
                        ): Assertion {
                            return $assertion->getUntemplatedCopy(
                                $class_template_params ?: [],
                                $lhs_var_id,
                                $codebase
                            );
                        },
                        $method_storage->if_false_assertions
                    )
                );
            }
        }

        if ($codebase->methods_to_rename) {
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            foreach ($codebase->methods_to_rename as $original_method_id => $new_method_name) {
                if ($declaring_method_id && (strtolower((string) $declaring_method_id)) === $original_method_id) {
                    $file_manipulations = [
                        new FileManipulation(
                            (int) $stmt_name->getAttribute('startFilePos'),
                            (int) $stmt_name->getAttribute('endFilePos') + 1,
                            $new_method_name
                        )
                    ];

                    FileManipulationBuffer::add(
                        $statements_analyzer->getFilePath(),
                        $file_manipulations
                    );
                }
            }
        }

        if ($config->eventDispatcher->hasAfterMethodCallAnalysisHandlers()) {
            $file_manipulations = [];

            $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            if ($appearing_method_id !== null && $declaring_method_id !== null) {
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

        return $return_type_candidate ?? Type::getMixed();
    }

    /**
     * Check properties accessed with magic getters and setters.
     * If `@psalm-seal-properties` is set, they must be defined.
     * If an `@property` annotation is specified, the setter must set something with the correct
     * type.
     */
    private static function getMagicGetterOrSetterProperty(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        PhpParser\Node\Identifier $stmt_name,
        Context $context,
        string $fq_class_name
    ): ?Union {
        $method_name = strtolower($stmt_name->name);
        if (!in_array($method_name, ['__get', '__set'], true)) {
            return null;
        }

        $codebase = $statements_analyzer->getCodebase();

        $first_arg_value = $stmt->getArgs()[0]->value ?? null;

        if (!$first_arg_value instanceof PhpParser\Node\Scalar\String_) {
            return null;
        }

        $prop_name = $first_arg_value->value;
        $property_id = $fq_class_name . '::$' . $prop_name;

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $codebase->properties->propertyExists(
            $property_id,
            $method_name === '__get',
            $statements_analyzer,
            $context,
            new CodeLocation($statements_analyzer->getSource(), $stmt)
        );

        switch ($method_name) {
            case '__set':
                // If `@psalm-seal-properties` is set, the property must be defined with
                // a `@property` annotation
                if (($class_storage->sealed_properties || $codebase->config->seal_all_properties)
                    && !isset($class_storage->pseudo_property_set_types['$' . $prop_name])
                    && IssueBuffer::accepts(
                        new UndefinedThisPropertyAssignment(
                            'Instance property ' . $property_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $property_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )
                ) {
                    // fall through
                }

                // If a `@property` annotation is set, the type of the value passed to the
                // magic setter must match the annotation.
                $second_arg_type = isset($stmt->getArgs()[1])
                    ? $statements_analyzer->node_data->getType($stmt->getArgs()[1]->value)
                    : null;

                if (isset($class_storage->pseudo_property_set_types['$' . $prop_name]) && $second_arg_type) {
                    $pseudo_set_type = TypeExpander::expandUnion(
                        $codebase,
                        $class_storage->pseudo_property_set_types['$' . $prop_name],
                        $fq_class_name,
                        new TNamedObject($fq_class_name),
                        $class_storage->parent_class
                    );

                    $union_comparison_results = new TypeComparisonResult();

                    $type_match_found = UnionTypeComparator::isContainedBy(
                        $codebase,
                        $second_arg_type,
                        $pseudo_set_type,
                        $second_arg_type->ignore_nullable_issues,
                        $second_arg_type->ignore_falsable_issues,
                        $union_comparison_results
                    );

                    if ($union_comparison_results->type_coerced) {
                        if ($union_comparison_results->type_coerced_from_mixed) {
                            IssueBuffer::maybeAdd(
                                new MixedPropertyTypeCoercion(
                                    $prop_name . ' expects \'' . $pseudo_set_type->getId() . '\', '
                                        . ' parent type `' . $second_arg_type . '` provided',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $property_id
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            );
                        } else {
                            IssueBuffer::maybeAdd(
                                new PropertyTypeCoercion(
                                    $prop_name . ' expects \'' . $pseudo_set_type->getId() . '\', '
                                        . ' parent type `' . $second_arg_type . '` provided',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $property_id
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            );
                        }
                    }

                    if (!$type_match_found && !$union_comparison_results->type_coerced_from_mixed) {
                        if (UnionTypeComparator::canBeContainedBy(
                            $codebase,
                            $second_arg_type,
                            $pseudo_set_type
                        )) {
                            IssueBuffer::maybeAdd(
                                new PossiblyInvalidPropertyAssignmentValue(
                                    $prop_name . ' with declared type \''
                                    . $pseudo_set_type
                                    . '\' cannot be assigned possibly different type \'' . $second_arg_type . '\'',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $property_id
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            );
                        } else {
                            IssueBuffer::maybeAdd(
                                new InvalidPropertyAssignmentValue(
                                    $prop_name . ' with declared type \''
                                    . $pseudo_set_type
                                    . '\' cannot be assigned type \'' . $second_arg_type . '\'',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $property_id
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            );
                        }
                    }
                }
                break;

            case '__get':
                // If `@psalm-seal-properties` is set, the property must be defined with
                // a `@property` annotation
                if (($class_storage->sealed_properties || $codebase->config->seal_all_properties)
                    && !isset($class_storage->pseudo_property_get_types['$' . $prop_name])
                    && IssueBuffer::accepts(
                        new UndefinedThisPropertyFetch(
                            'Instance property ' . $property_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $property_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )
                ) {
                    // fall through
                }

                if (isset($class_storage->pseudo_property_get_types['$' . $prop_name])) {
                    return clone $class_storage->pseudo_property_get_types['$' . $prop_name];
                }

                break;
        }

        return null;
    }
}
