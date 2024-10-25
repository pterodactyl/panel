<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Assignment;

use PhpParser;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt\PropertyProperty;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\ClassAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\AtomicPropertyFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Codebase\Methods;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\DeprecatedProperty;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\ImpurePropertyAssignment;
use Psalm\Issue\InaccessibleProperty;
use Psalm\Issue\InternalClass;
use Psalm\Issue\InternalProperty;
use Psalm\Issue\InvalidPropertyAssignment;
use Psalm\Issue\InvalidPropertyAssignmentValue;
use Psalm\Issue\LoopInvalidation;
use Psalm\Issue\MixedAssignment;
use Psalm\Issue\MixedPropertyAssignment;
use Psalm\Issue\MixedPropertyTypeCoercion;
use Psalm\Issue\NoInterfaceProperties;
use Psalm\Issue\NullPropertyAssignment;
use Psalm\Issue\PossiblyFalsePropertyAssignmentValue;
use Psalm\Issue\PossiblyInvalidPropertyAssignment;
use Psalm\Issue\PossiblyInvalidPropertyAssignmentValue;
use Psalm\Issue\PossiblyNullPropertyAssignment;
use Psalm\Issue\PossiblyNullPropertyAssignmentValue;
use Psalm\Issue\PropertyTypeCoercion;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedMagicPropertyAssignment;
use Psalm\Issue\UndefinedPropertyAssignment;
use Psalm\Issue\UndefinedThisPropertyAssignment;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Scalar\VirtualString;
use Psalm\Node\VirtualArg;
use Psalm\Node\VirtualIdentifier;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\PropertyStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_merge;
use function array_pop;
use function count;
use function in_array;
use function reset;
use function strpos;
use function strtolower;

/**
 * @internal
 */
class InstancePropertyAssignmentAnalyzer
{
    /**
     * @param   PropertyFetch|PropertyProperty  $stmt
     * @param   bool                            $direct_assignment whether the variable is assigned explicitly
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\NodeAbstract $stmt,
        string $prop_name,
        ?PhpParser\Node\Expr $assignment_value,
        Union $assignment_value_type,
        Context $context,
        bool $direct_assignment = true
    ): ?bool {
        $codebase = $statements_analyzer->getCodebase();

        if ($stmt instanceof PropertyProperty) {
            if (!$context->self || !$stmt->default) {
                return null;
            }

            $property_id = $context->self . '::$' . $prop_name;

            $class_property_type = null;

            try {
                $class_property_type = $codebase->properties->getPropertyType(
                    $property_id,
                    true,
                    $statements_analyzer,
                    $context
                );
            } catch (UnexpectedValueException $e) {
                // do nothing
            }

            if ($class_property_type) {
                $class_storage = $codebase->classlike_storage_provider->get($context->self);

                $class_property_type = self::getExpandedPropertyType(
                    $codebase,
                    $context->self,
                    $prop_name,
                    $class_storage
                );
            }

            $var_id = '$this->' . $prop_name;

            $assigned_properties = [
                new AssignedProperty(
                    $class_property_type ?? Type::getMixed(),
                    $property_id,
                    $assignment_value_type
                )
            ];
        } else {
            $assigned_properties = self::analyzeRegularAssignment(
                $statements_analyzer,
                $stmt,
                $assignment_value,
                $context,
                $direct_assignment,
                $codebase,
                $assignment_value_type,
                $prop_name,
                $var_id
            );
        }

        if (!$assigned_properties) {
            return null;
        }

        if ($assignment_value_type->hasMixed()) {
            return null;
        }

        $invalid_assignment_value_types = [];

        $has_valid_assignment_value_type = false;

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
            && count($assigned_properties) === 1
        ) {
            $codebase->analyzer->addNodeType(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                $assigned_properties[0]->property_type->getId()
            );
        }

        foreach ($assigned_properties as $assigned_property) {
            $class_property_type = $assigned_property->property_type;
            $assignment_type = $assigned_property->assignment_type;

            if ($class_property_type->hasMixed()) {
                continue;
            }

            $union_comparison_results = new TypeComparisonResult();

            $type_match_found = UnionTypeComparator::isContainedBy(
                $codebase,
                $assignment_type,
                $class_property_type,
                true,
                true,
                $union_comparison_results
            );

            if ($type_match_found && $union_comparison_results->replacement_union_type) {
                if ($var_id) {
                    $context->vars_in_scope[$var_id] = $union_comparison_results->replacement_union_type;
                }
            }

            if ($union_comparison_results->type_coerced) {
                if ($union_comparison_results->type_coerced_from_mixed) {
                    IssueBuffer::maybeAdd(
                        new MixedPropertyTypeCoercion(
                            $var_id . ' expects \'' . $class_property_type->getId() . '\', '
                                . ' parent type `' . $assignment_type->getId() . '` provided',
                            new CodeLocation(
                                $statements_analyzer->getSource(),
                                $assignment_value ?? $stmt,
                                $context->include_location
                            ),
                            $assigned_property->id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new PropertyTypeCoercion(
                            $var_id . ' expects \'' . $class_property_type->getId() . '\', '
                                . ' parent type \'' . $assignment_type->getId() . '\' provided',
                            new CodeLocation(
                                $statements_analyzer->getSource(),
                                $assignment_value ?? $stmt,
                                $context->include_location
                            ),
                            $assigned_property->id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                }
            }

            if ($union_comparison_results->to_string_cast) {
                IssueBuffer::maybeAdd(
                    new ImplicitToStringCast(
                        $var_id . ' expects \'' . $class_property_type . '\', '
                            . '\'' . $assignment_type . '\' provided with a __toString method',
                        new CodeLocation(
                            $statements_analyzer->getSource(),
                            $assignment_value ?? $stmt,
                            $context->include_location
                        )
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }

            if (!$type_match_found && !$union_comparison_results->type_coerced) {
                if (UnionTypeComparator::canBeContainedBy(
                    $codebase,
                    $assignment_type,
                    $class_property_type,
                    true,
                    true
                )) {
                    $has_valid_assignment_value_type = true;
                }

                $invalid_assignment_value_types[$assigned_property->id] = $class_property_type->getId();
            } else {
                $has_valid_assignment_value_type = true;
            }

            if ($type_match_found) {
                if (!$assignment_type->ignore_nullable_issues
                    && $assignment_type->isNullable()
                    && !$class_property_type->isNullable()
                ) {
                    if (IssueBuffer::accepts(
                        new PossiblyNullPropertyAssignmentValue(
                            $var_id . ' with non-nullable declared type \'' . $class_property_type .
                                '\' cannot be assigned nullable type \'' . $assignment_type . '\'',
                            new CodeLocation(
                                $statements_analyzer->getSource(),
                                $assignment_value ?? $stmt,
                                $context->include_location
                            ),
                            $assigned_property->id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }

                if (!$assignment_type->ignore_falsable_issues
                    && $assignment_type->isFalsable()
                    && !$class_property_type->hasBool()
                    && !$class_property_type->hasScalar()
                ) {
                    if (IssueBuffer::accepts(
                        new PossiblyFalsePropertyAssignmentValue(
                            $var_id . ' with non-falsable declared type \'' . $class_property_type .
                                '\' cannot be assigned possibly false type \'' . $assignment_type . '\'',
                            new CodeLocation(
                                $statements_analyzer->getSource(),
                                $assignment_value ?? $stmt,
                                $context->include_location
                            ),
                            $assigned_property->id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }
            }
        }

        foreach ($invalid_assignment_value_types as $property_id => $invalid_class_property_type) {
            if (!$has_valid_assignment_value_type) {
                if (IssueBuffer::accepts(
                    new InvalidPropertyAssignmentValue(
                        $var_id . ' with declared type \'' . $invalid_class_property_type .
                            '\' cannot be assigned type \'' . $assignment_value_type->getId() . '\'',
                        new CodeLocation(
                            $statements_analyzer->getSource(),
                            $assignment_value ?? $stmt,
                            $context->include_location
                        ),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }
            } else {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidPropertyAssignmentValue(
                        $var_id . ' with declared type \'' . $invalid_class_property_type .
                            '\' cannot be assigned possibly different type \'' .
                            $assignment_value_type->getId() . '\'',
                        new CodeLocation(
                            $statements_analyzer->getSource(),
                            $assignment_value ?? $stmt,
                            $context->include_location
                        ),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        }

        return null;
    }

    public static function trackPropertyImpurity(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        string $property_id,
        PropertyStorage $property_storage,
        ClassLikeStorage $declaring_class_storage,
        Context $context
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        $stmt_var_type = $statements_analyzer->node_data->getType($stmt->var);

        $property_var_pure_compatible = $stmt_var_type
            && $stmt_var_type->reference_free
            && $stmt_var_type->allow_mutations;

        $appearing_property_class = $codebase->properties->getAppearingClassForProperty(
            $property_id,
            true
        );

        $project_analyzer = $statements_analyzer->getProjectAnalyzer();

        if ($appearing_property_class && ($property_storage->readonly || $codebase->alter_code)) {
            $can_set_readonly_property = $context->self
                && $context->calling_method_id
                && ($appearing_property_class === $context->self
                    || $codebase->classExtends($context->self, $appearing_property_class))
                && (strpos($context->calling_method_id, '::__construct')
                    || strpos($context->calling_method_id, '::unserialize')
                    || strpos($context->calling_method_id, '::__unserialize')
                    || strpos($context->calling_method_id, '::__clone')
                    || $property_storage->allow_private_mutation
                    || $property_var_pure_compatible);

            if (!$can_set_readonly_property) {
                if ($property_storage->readonly) {
                    IssueBuffer::maybeAdd(
                        new InaccessibleProperty(
                            $property_id . ' is marked readonly',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                } elseif (!$declaring_class_storage->mutation_free
                    && isset($project_analyzer->getIssuesToFix()['MissingImmutableAnnotation'])
                    && $statements_analyzer->getSource()
                        instanceof FunctionLikeAnalyzer
                ) {
                    $codebase->analyzer->addMutableClass($declaring_class_storage->name);
                }
            }
        }
    }

    public static function analyzeStatement(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Property $stmt,
        Context $context
    ): void {
        foreach ($stmt->props as $prop) {
            if ($prop->default) {
                if ($stmt->isReadonly()) {
                    IssueBuffer::maybeAdd(
                        new InvalidPropertyAssignment(
                            'Readonly property ' . $context->self . '::$' . $prop->name->name
                                . ' cannot have a default',
                            new CodeLocation($statements_analyzer->getSource(), $prop->default)
                        )
                    );
                }

                ExpressionAnalyzer::analyze($statements_analyzer, $prop->default, $context);

                if ($prop_default_type = $statements_analyzer->node_data->getType($prop->default)) {
                    if (self::analyze(
                        $statements_analyzer,
                        $prop,
                        $prop->name->name,
                        $prop->default,
                        $prop_default_type,
                        $context
                    ) === false) {
                        // fall through
                    }
                }
            }
        }
    }

    private static function taintProperty(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        string $property_id,
        ClassLikeStorage $class_storage,
        Union $assignment_value_type,
        Context $context
    ): void {
        if (!$statements_analyzer->data_flow_graph) {
            return;
        }

        $codebase = $statements_analyzer->getCodebase();

        $data_flow_graph = $statements_analyzer->data_flow_graph;

        $var_location = new CodeLocation($statements_analyzer->getSource(), $stmt->var);
        $property_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

        if ($class_storage->specialize_instance) {
            $var_id = ExpressionIdentifier::getArrayVarId(
                $stmt->var,
                null,
                $statements_analyzer
            );

            $var_property_id = ExpressionIdentifier::getArrayVarId(
                $stmt,
                null,
                $statements_analyzer
            );

            if ($var_id) {
                if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                    && in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
                ) {
                    $context->vars_in_scope[$var_id]->parent_nodes = [];
                    return;
                }

                $var_node = DataFlowNode::getForAssignment(
                    $var_id,
                    $var_location
                );

                $data_flow_graph->addNode($var_node);

                $property_node = DataFlowNode::getForAssignment(
                    $var_property_id ?: $var_id . '->$property',
                    $property_location
                );

                $data_flow_graph->addNode($property_node);

                $event = new AddRemoveTaintsEvent($stmt, $context, $statements_analyzer, $codebase);

                $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
                $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);

                $data_flow_graph->addPath(
                    $property_node,
                    $var_node,
                    'property-assignment'
                        . ($stmt->name instanceof PhpParser\Node\Identifier ? '-' . $stmt->name : ''),
                    $added_taints,
                    $removed_taints
                );

                if ($assignment_value_type->parent_nodes) {
                    foreach ($assignment_value_type->parent_nodes as $parent_node) {
                        $data_flow_graph->addPath($parent_node, $property_node, '=', $added_taints, $removed_taints);
                    }
                }

                $stmt_var_type = clone $context->vars_in_scope[$var_id];

                if ($context->vars_in_scope[$var_id]->parent_nodes) {
                    foreach ($context->vars_in_scope[$var_id]->parent_nodes as $parent_node) {
                        $data_flow_graph->addPath($parent_node, $var_node, '=', $added_taints, $removed_taints);
                    }
                }

                $stmt_var_type->parent_nodes = [$var_node->id => $var_node];

                $context->vars_in_scope[$var_id] = $stmt_var_type;
            }
        } else {
            if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                && in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
            ) {
                $assignment_value_type->parent_nodes = [];
                return;
            }

            $var_property_id = ExpressionIdentifier::getArrayVarId(
                $stmt,
                null,
                $statements_analyzer
            );

            $localized_property_node = DataFlowNode::getForAssignment(
                $var_property_id
                    ?: $property_id . '-' . $property_location->file_name . ':' . $property_location->raw_file_start,
                $property_location
            );

            $data_flow_graph->addNode($localized_property_node);

            $property_node = new DataFlowNode(
                $property_id,
                $property_id,
                null,
                null
            );

            $data_flow_graph->addNode($property_node);

            $event = new AddRemoveTaintsEvent($stmt, $context, $statements_analyzer, $codebase);

            $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
            $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);

            $data_flow_graph->addPath(
                $localized_property_node,
                $property_node,
                'property-assignment',
                $added_taints,
                $removed_taints
            );

            if ($assignment_value_type->parent_nodes) {
                foreach ($assignment_value_type->parent_nodes as $parent_node) {
                    $data_flow_graph->addPath(
                        $parent_node,
                        $localized_property_node,
                        '=',
                        $added_taints,
                        $removed_taints
                    );
                }
            }

            $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
                $property_id,
                false,
                $statements_analyzer
            );

            if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                && $declaring_property_class
                && $declaring_property_class !== $class_storage->name
                && $stmt->name instanceof PhpParser\Node\Identifier
            ) {
                $declaring_property_node = new DataFlowNode(
                    $declaring_property_class . '::$' . $stmt->name,
                    $declaring_property_class . '::$' . $stmt->name,
                    null,
                    null
                );

                $data_flow_graph->addNode($declaring_property_node);

                $data_flow_graph->addPath(
                    $property_node,
                    $declaring_property_node,
                    'property-assignment',
                    $added_taints,
                    $removed_taints
                );
            }
        }
    }

    /**
     * @return list<AssignedProperty>
     */
    private static function analyzeRegularAssignment(
        StatementsAnalyzer $statements_analyzer,
        PropertyFetch $stmt,
        ?PhpParser\Node\Expr $assignment_value,
        Context $context,
        bool $direct_assignment,
        Codebase $codebase,
        Union $assignment_value_type,
        string $prop_name,
        ?string &$var_id
    ): array {
        $was_inside_general_use = $context->inside_general_use;
        $context->inside_general_use = true;

        ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context);

        $context->inside_general_use = $was_inside_general_use;

        $lhs_type = $statements_analyzer->node_data->getType($stmt->var);

        if ($lhs_type === null) {
            return [];
        }

        $lhs_var_id = ExpressionIdentifier::getVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $var_id = ExpressionIdentifier::getVarId(
            $stmt,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        if ($var_id) {
            $context->assigned_var_ids[$var_id] = (int)$stmt->var->getAttribute('startFilePos');

            if ($direct_assignment && isset($context->protected_var_ids[$var_id])) {
                if (IssueBuffer::accepts(
                    new LoopInvalidation(
                        'Variable ' . $var_id . ' has already been assigned in a for/foreach loop',
                        new CodeLocation($statements_analyzer->getSource(), $stmt->var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                }
            }
        }

        if ($lhs_type->hasMixed()) {
            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
            }

            if ($stmt->name instanceof PhpParser\Node\Identifier) {
                $codebase->analyzer->addMixedMemberName(
                    '$' . $stmt->name->name,
                    $context->calling_method_id ?: $statements_analyzer->getFileName()
                );
            }

            if (IssueBuffer::accepts(
                new MixedPropertyAssignment(
                    $lhs_var_id . ' of type mixed cannot be assigned to',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->var)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
            }

            return [];
        }

        if (!$context->collect_initializations
            && !$context->collect_mutations
            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
            && (!(($parent_source = $statements_analyzer->getSource())
                    instanceof FunctionLikeAnalyzer)
                || !$parent_source->getSource() instanceof TraitAnalyzer)
        ) {
            $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
        }

        if ($lhs_type->isNull()) {
            if (IssueBuffer::accepts(
                new NullPropertyAssignment(
                    $lhs_var_id . ' of type null cannot be assigned to',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->var)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
            }

            return [];
        }

        if ($lhs_type->isNullable() && !$lhs_type->ignore_nullable_issues) {
            if (IssueBuffer::accepts(
                new PossiblyNullPropertyAssignment(
                    $lhs_var_id . ' with possibly null type \'' . $lhs_type . '\' cannot be assigned to',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->var)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
            }
        }

        $has_regular_setter = false;

        $invalid_assignment_types = [];

        $has_valid_assignment_type = false;

        $lhs_atomic_types = $lhs_type->getAtomicTypes();

        $assigned_properties = [];

        $context_type = null;

        while ($lhs_atomic_types) {
            $lhs_type_part = array_pop($lhs_atomic_types);

            if ($lhs_type_part instanceof TTemplateParam) {
                $lhs_atomic_types = array_merge(
                    $lhs_atomic_types,
                    $lhs_type_part->as->getAtomicTypes()
                );

                continue;
            }

            $assigned_property = self::analyzeAtomicAssignment(
                $statements_analyzer,
                $codebase,
                $stmt,
                $assignment_value,
                $prop_name,
                $context,
                $lhs_type,
                $lhs_type_part,
                $invalid_assignment_types,
                $var_id,
                $assignment_value_type,
                $lhs_var_id,
                $has_valid_assignment_type,
                $has_regular_setter
            );

            if ($assigned_property) {
                $assigned_properties[] = $assigned_property;

                if ($context_type) {
                    $context_type = Type::combineUnionTypes(
                        $context_type,
                        $assigned_property->assignment_type,
                        $codebase
                    );
                } else {
                    $context_type = $assigned_property->assignment_type;
                }
            }
        }

        if ($invalid_assignment_types) {
            $invalid_assignment_type = $invalid_assignment_types[0];

            if (!$has_valid_assignment_type) {
                if (IssueBuffer::accepts(
                    new InvalidPropertyAssignment(
                        $lhs_var_id . ' with non-object type \'' . $invalid_assignment_type .
                        '\' cannot treated as an object',
                        new CodeLocation($statements_analyzer->getSource(), $stmt->var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                }
            } else {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidPropertyAssignment(
                        $lhs_var_id . ' with possible non-object type \'' . $invalid_assignment_type .
                        '\' cannot treated as an object',
                        new CodeLocation($statements_analyzer->getSource(), $stmt->var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                }
            }
        }

        if (!$has_regular_setter) {
            return [];
        }

        $context_type = $context_type ?: $assignment_value_type;

        if ($var_id) {
            if ($context->collect_initializations
                && $lhs_var_id === '$this'
            ) {
                $context_type->initialized_class = $context->self;
            }

            // because we don't want to be assigning for property declarations
            $context->vars_in_scope[$var_id] = $context_type;
        }

        return $assigned_properties;
    }

    /**
     * @param list<string> $invalid_assignment_types
     */
    private static function analyzeAtomicAssignment(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PropertyFetch $stmt,
        ?PhpParser\Node\Expr $assignment_value,
        string $prop_name,
        Context $context,
        Union $lhs_type,
        Atomic $lhs_type_part,
        array &$invalid_assignment_types,
        ?string $var_id,
        Union $assignment_value_type,
        ?string $lhs_var_id,
        bool &$has_valid_assignment_type,
        bool &$has_regular_setter
    ): ?AssignedProperty {
        if ($lhs_type_part instanceof TNull) {
            return null;
        }

        if ($lhs_type_part instanceof TFalse
            && $lhs_type->ignore_falsable_issues
            && count($lhs_type->getAtomicTypes()) > 1
        ) {
            return null;
        }

        if (!$lhs_type_part instanceof TObject && !$lhs_type_part instanceof TNamedObject) {
            $invalid_assignment_types[] = (string)$lhs_type_part;

            return null;
        }

        $has_valid_assignment_type = true;

        // stdClass and SimpleXMLElement are special cases where we cannot infer the return types
        // but we don't want to throw an error
        // Hack has a similar issue: https://github.com/facebook/hhvm/issues/5164
        if ($lhs_type_part instanceof TObject ||
            (
            in_array(
                strtolower($lhs_type_part->value),
                Config::getInstance()->getUniversalObjectCrates() + [
                    'dateinterval',
                    'domdocument',
                    'domnode'
                ],
                true
            )
            )
        ) {
            if ($var_id) {
                if ($lhs_type_part instanceof TNamedObject &&
                    strtolower($lhs_type_part->value) === 'stdclass'
                ) {
                    $context->vars_in_scope[$var_id] = $assignment_value_type;
                } else {
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                }
            }

            return null;
        }

        if (ExpressionAnalyzer::isMock($lhs_type_part->value)) {
            if ($var_id) {
                $context->vars_in_scope[$var_id] = Type::getMixed();
            }

            return null;
        }

        $intersection_types = $lhs_type_part->getIntersectionTypes() ?: [];

        $fq_class_name = $lhs_type_part->value;

        $override_property_visibility = false;

        $class_exists = false;
        $interface_exists = false;

        if (!$codebase->classExists($lhs_type_part->value)) {
            if ($codebase->interfaceExists($lhs_type_part->value)) {
                $interface_exists = true;
                $interface_storage = $codebase->classlike_storage_provider->get(
                    strtolower($lhs_type_part->value)
                );

                $override_property_visibility = $interface_storage->override_property_visibility;

                foreach ($intersection_types as $intersection_type) {
                    if ($intersection_type instanceof TNamedObject
                        && $codebase->classExists($intersection_type->value)
                    ) {
                        $fq_class_name = $intersection_type->value;
                        $class_exists = true;
                        break;
                    }
                }

                if (!$class_exists) {
                    if (IssueBuffer::accepts(
                        new NoInterfaceProperties(
                            'Interfaces cannot have properties',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $lhs_type_part->value
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return null;
                    }

                    if (!$codebase->methods->methodExists(
                        new MethodIdentifier(
                            $fq_class_name,
                            '__set'
                        )
                    )) {
                        return null;
                    }
                }
            }

            if (!$class_exists && !$interface_exists) {
                IssueBuffer::maybeAdd(
                    new UndefinedClass(
                        'Cannot set properties of undefined class ' . $lhs_type_part->value,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $lhs_type_part->value
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );

                return null;
            }
        } else {
            $class_exists = true;
        }

        $property_id = $fq_class_name . '::$' . $prop_name;

        $has_magic_setter = false;

        $set_method_id = new MethodIdentifier($fq_class_name, '__set');

        if ((!$codebase->properties->propertyExists($property_id, false, $statements_analyzer, $context)
                || ($lhs_var_id !== '$this'
                    && $fq_class_name !== $context->self
                    && ClassLikeAnalyzer::checkPropertyVisibility(
                        $property_id,
                        $context,
                        $statements_analyzer,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $statements_analyzer->getSuppressedIssues(),
                        false
                    ) !== true)
            )
            && $codebase->methods->methodExists(
                $set_method_id,
                $context->calling_method_id,
                $codebase->collect_locations
                    ? new CodeLocation($statements_analyzer->getSource(), $stmt)
                    : null,
                !$context->collect_initializations
                && !$context->collect_mutations
                    ? $statements_analyzer
                    : null,
                $statements_analyzer->getFilePath()
            )
        ) {
            $has_magic_setter = true;
            $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

            if ($var_id) {
                if (isset($class_storage->pseudo_property_set_types['$' . $prop_name])) {
                    $class_property_type = TypeExpander::expandUnion(
                        $codebase,
                        clone $class_storage->pseudo_property_set_types['$' . $prop_name],
                        $fq_class_name,
                        $fq_class_name,
                        $class_storage->parent_class
                    );

                    $has_regular_setter = true;

                    if (!$context->collect_initializations && !$context->collect_mutations) {
                        self::taintProperty(
                            $statements_analyzer,
                            $stmt,
                            $property_id,
                            $class_storage,
                            $assignment_value_type,
                            $context
                        );
                    }

                    return new AssignedProperty(
                        $class_property_type,
                        $property_id,
                        $assignment_value_type
                    );
                }
            }

            if ($assignment_value) {
                self::analyzeSetCall(
                    $var_id,
                    $context,
                    $statements_analyzer,
                    $stmt,
                    $prop_name,
                    $assignment_value
                );
            }

            /*
             * If we have an explicit list of all allowed magic properties on the class, and we're
             * not in that list, fall through
             */
            if (!$var_id || !$class_storage->sealed_properties) {
                if (!$context->collect_initializations && !$context->collect_mutations) {
                    self::taintProperty(
                        $statements_analyzer,
                        $stmt,
                        $property_id,
                        $class_storage,
                        $assignment_value_type,
                        $context
                    );
                }

                return null;
            }

            if (!$class_exists) {
                IssueBuffer::maybeAdd(
                    new UndefinedMagicPropertyAssignment(
                        'Magic instance property ' . $property_id . ' is not defined',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }
        }

        if (!$class_exists) {
            return null;
        }

        $has_regular_setter = true;

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable
            && $stmt->var->name === 'this'
            && $context->self
        ) {
            $self_property_id = $context->self . '::$' . $prop_name;

            if ($self_property_id !== $property_id
                && $codebase->properties->propertyExists(
                    $self_property_id,
                    false,
                    $statements_analyzer,
                    $context
                )
            ) {
                $property_id = $self_property_id;
            }
        }

        if ($statements_analyzer->data_flow_graph
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

            self::taintProperty(
                $statements_analyzer,
                $stmt,
                $property_id,
                $class_storage,
                $assignment_value_type,
                $context
            );
        }

        if (!$codebase->properties->propertyExists(
            $property_id,
            false,
            $statements_analyzer,
            $context,
            new CodeLocation($statements_analyzer->getSource(), $stmt)
        )
        // when property existence is asserted by a plugin it doesn't necessarily has storage
        || ($codebase->properties->hasStorage($property_id)
            && $codebase->properties->getStorage($property_id)->is_static
        )
        ) {
            if ($stmt->var instanceof PhpParser\Node\Expr\Variable && $stmt->var->name === 'this') {
                // if this is a proper error, we'll see it on the first pass
                if ($context->collect_mutations) {
                    return null;
                }

                IssueBuffer::maybeAdd(
                    new UndefinedThisPropertyAssignment(
                        'Instance property ' . $property_id . ' is not defined',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            } else {
                if ($has_magic_setter) {
                    IssueBuffer::maybeAdd(
                        new UndefinedMagicPropertyAssignment(
                            'Magic instance property ' . $property_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $property_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new UndefinedPropertyAssignment(
                            'Instance property ' . $property_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $property_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                }
            }

            return null;
        }

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            $codebase->analyzer->addNodeReference(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                $property_id
            );
        }

        if (!$override_property_visibility) {
            if (!$context->collect_mutations) {
                if (ClassLikeAnalyzer::checkPropertyVisibility(
                    $property_id,
                    $context,
                    $statements_analyzer,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $statements_analyzer->getSuppressedIssues()
                ) === false) {
                    return null;
                }
            } else {
                if (ClassLikeAnalyzer::checkPropertyVisibility(
                    $property_id,
                    $context,
                    $statements_analyzer,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $statements_analyzer->getSuppressedIssues(),
                    false
                ) !== true) {
                    return null;
                }
            }
        }

        $declaring_property_class = (string)$codebase->properties->getDeclaringClassForProperty(
            $property_id,
            false
        );

        if ($codebase->properties_to_rename) {
            $declaring_property_id = strtolower($declaring_property_class) . '::$' . $prop_name;

            foreach ($codebase->properties_to_rename as $original_property_id => $new_property_name) {
                if ($declaring_property_id === $original_property_id) {
                    $file_manipulations = [
                        new FileManipulation(
                            (int)$stmt->name->getAttribute('startFilePos'),
                            (int)$stmt->name->getAttribute('endFilePos') + 1,
                            $new_property_name
                        )
                    ];

                    FileManipulationBuffer::add(
                        $statements_analyzer->getFilePath(),
                        $file_manipulations
                    );
                }
            }
        }

        $declaring_class_storage = $codebase->classlike_storage_provider->get($declaring_property_class);

        if (isset($declaring_class_storage->properties[$prop_name])) {
            $property_storage = $declaring_class_storage->properties[$prop_name];

            if ($property_storage->deprecated) {
                IssueBuffer::maybeAdd(
                    new DeprecatedProperty(
                        $property_id . ' is marked deprecated',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }

            if ($context->self && !NamespaceAnalyzer::isWithinAny($context->self, $property_storage->internal)) {
                IssueBuffer::maybeAdd(
                    new InternalProperty(
                        $property_id . ' is internal to ' . InternalClass::listToPhrase($property_storage->internal)
                            . ' but called from ' . $context->self,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }

            self::trackPropertyImpurity(
                $statements_analyzer,
                $stmt,
                $property_id,
                $property_storage,
                $declaring_class_storage,
                $context
            );

            if (!$property_storage->readonly
                && !$context->collect_mutations
                && !$context->collect_initializations
                && isset($context->vars_in_scope[$lhs_var_id])
                && !$context->vars_in_scope[$lhs_var_id]->allow_mutations
            ) {
                if ($context->mutation_free) {
                    IssueBuffer::maybeAdd(
                        new ImpurePropertyAssignment(
                            'Cannot assign to a property from a mutation-free context',
                            new CodeLocation($statements_analyzer, $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                } elseif ($statements_analyzer->getSource()
                    instanceof FunctionLikeAnalyzer
                    && $statements_analyzer->getSource()->track_mutations
                ) {
                    $statements_analyzer->getSource()->inferred_impure = true;
                }
            }

            if ($property_storage->getter_method) {
                $getter_id = $lhs_var_id . '->' . $property_storage->getter_method . '()';

                unset($context->vars_in_scope[$getter_id]);
            }
        }

        $class_property_type = $codebase->properties->getPropertyType(
            $property_id,
            true,
            $statements_analyzer,
            $context
        );

        if (!$class_property_type
            || (isset($declaring_class_storage->properties[$prop_name])
                && !$declaring_class_storage->properties[$prop_name]->type_location)
        ) {
            if (!$class_property_type) {
                $class_property_type = Type::getMixed();
            }

            $source_analyzer = $statements_analyzer->getSource()->getSource();

            if ($lhs_var_id === '$this'
                && $source_analyzer instanceof ClassAnalyzer
            ) {
                $source_analyzer->inferred_property_types[$prop_name] = Type::combineUnionTypes(
                    $assignment_value_type,
                    $source_analyzer->inferred_property_types[$prop_name] ?? null
                );
            }
        }

        if (!$class_property_type->isMixed()) {
            $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

            $class_property_type = TypeExpander::expandUnion(
                $codebase,
                clone $class_property_type,
                $fq_class_name,
                $lhs_type_part,
                $declaring_class_storage->parent_class,
                true,
                false,
                $class_storage->final
            );

            $class_property_type = Methods::localizeType(
                $codebase,
                $class_property_type,
                $fq_class_name,
                $declaring_property_class
            );

            if ($lhs_type_part instanceof TGenericObject) {
                $class_property_type = AtomicPropertyFetchAnalyzer::localizePropertyType(
                    $codebase,
                    $class_property_type,
                    $lhs_type_part,
                    $class_storage,
                    $declaring_class_storage
                );
            }

            $assignment_value_type = Methods::localizeType(
                $codebase,
                $assignment_value_type,
                $fq_class_name,
                $declaring_property_class
            );

            if (!$class_property_type->hasMixed() && $assignment_value_type->hasMixed()) {
                $origin_locations = [];

                if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph) {
                    foreach ($assignment_value_type->parent_nodes as $parent_node) {
                        $origin_locations = array_merge(
                            $origin_locations,
                            $statements_analyzer->data_flow_graph->getOriginLocations($parent_node)
                        );
                    }
                }

                $origin_location = count($origin_locations) === 1 ? reset($origin_locations) : null;

                $message = $var_id
                    ? 'Unable to determine the type that ' . $var_id . ' is being assigned to'
                    : 'Unable to determine the type of this assignment';

                if ($origin_location && $origin_location->getLineNumber() === $stmt->getLine()) {
                    $origin_location = null;
                }

                IssueBuffer::maybeAdd(
                    new MixedAssignment(
                        $message,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $origin_location
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }
        }

        return new AssignedProperty(
            $class_property_type,
            $property_id,
            $assignment_value_type
        );
    }

    public static function getExpandedPropertyType(
        Codebase $codebase,
        string $fq_class_name,
        string $property_name,
        ClassLikeStorage $storage
    ): ?Union {
        $property_class_name = $codebase->properties->getDeclaringClassForProperty(
            $fq_class_name . '::$' . $property_name,
            true
        );

        if ($property_class_name === null) {
            return null;
        }

        $property_class_storage = $codebase->classlike_storage_provider->get($property_class_name);

        $property_storage = $property_class_storage->properties[$property_name];

        if (!$property_storage->type) {
            return null;
        }

        $property_type = clone $property_storage->type;

        $fleshed_out_type = !$property_type->isMixed()
            ? TypeExpander::expandUnion(
                $codebase,
                $property_type,
                $fq_class_name,
                $fq_class_name,
                $storage->parent_class,
                true,
                false,
                $storage->final
            )
            : $property_type;

        $class_template_params = ClassTemplateParamCollector::collect(
            $codebase,
            $property_class_storage,
            $storage,
            null,
            new TNamedObject($fq_class_name),
            true
        );

        $template_result = new TemplateResult(
            $class_template_params ?: [],
            []
        );

        if ($class_template_params) {
            $fleshed_out_type = TemplateStandinTypeReplacer::replace(
                $fleshed_out_type,
                $template_result,
                $codebase,
                null,
                null,
                null
            );
        }

        return $fleshed_out_type;
    }

    private static function analyzeSetCall(
        ?string $var_id,
        Context $context,
        StatementsAnalyzer $statements_analyzer,
        PropertyFetch $stmt,
        string $prop_name,
        Expr $assignment_value
    ): void {
        if ($var_id) {
            $context->removeVarFromConflictingClauses(
                $var_id,
                Type::getMixed(),
                $statements_analyzer
            );

            unset($context->vars_in_scope[$var_id]);
        }

        $old_data_provider = $statements_analyzer->node_data;

        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        $fake_method_call = new VirtualMethodCall(
            $stmt->var,
            new VirtualIdentifier('__set', $stmt->name->getAttributes()),
            [
                new VirtualArg(
                    new VirtualString(
                        $prop_name,
                        $stmt->name->getAttributes()
                    )
                ),
                new VirtualArg(
                    $assignment_value
                )
            ]
        );

        $suppressed_issues = $statements_analyzer->getSuppressedIssues();

        if (!in_array('PossiblyNullReference', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['PossiblyNullReference']);
        }

        MethodCallAnalyzer::analyze(
            $statements_analyzer,
            $fake_method_call,
            $context,
            false
        );

        if (!in_array('PossiblyNullReference', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['PossiblyNullReference']);
        }

        $statements_analyzer->node_data = $old_data_provider;
    }
}
