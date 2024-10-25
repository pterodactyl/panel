<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Assignment;

use InvalidArgumentException;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Issue\InvalidArrayAssignment;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TDependentListKey;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateIndexedAccess;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Union;

use function array_pop;
use function array_reverse;
use function array_shift;
use function array_slice;
use function array_unshift;
use function count;
use function implode;
use function in_array;
use function is_string;
use function preg_match;
use function strlen;

/**
 * @internal
 */
class ArrayAssignmentAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context,
        ?PhpParser\Node\Expr $assign_value,
        Union $assignment_value_type
    ): void {
        $nesting = 0;
        $var_id = ExpressionIdentifier::getVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
            $nesting
        );

        self::updateArrayType(
            $statements_analyzer,
            $stmt,
            $assign_value,
            $assignment_value_type,
            $context
        );

        if (!$statements_analyzer->node_data->getType($stmt->var) && $var_id) {
            $context->vars_in_scope[$var_id] = Type::getMixed();
        }
    }

    /**
     * @return false|null
     * @psalm-suppress PossiblyUnusedReturnValue not used but seems important
     */
    public static function updateArrayType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        ?PhpParser\Node\Expr $assign_value,
        Union $assignment_type,
        Context $context
    ): ?bool {
        $root_array_expr = $stmt;

        $child_stmts = [];

        while ($root_array_expr->var instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            $child_stmts[] = $root_array_expr;
            $root_array_expr = $root_array_expr->var;
        }

        $child_stmts[] = $root_array_expr;
        $root_array_expr = $root_array_expr->var;

        if (ExpressionAnalyzer::analyze(
            $statements_analyzer,
            $root_array_expr,
            $context,
            true
        ) === false) {
            // fall through
        }

        $codebase = $statements_analyzer->getCodebase();

        $root_type = $statements_analyzer->node_data->getType($root_array_expr) ?? Type::getMixed();

        if ($root_type->hasMixed()) {
            if (ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $stmt->var,
                $context,
                true
            ) === false) {
                // fall through
            }

            if ($stmt->dim) {
                if (ExpressionAnalyzer::analyze(
                    $statements_analyzer,
                    $stmt->dim,
                    $context
                ) === false) {
                    // fall through
                }
            }
        }

        $current_type = $root_type;

        $current_dim = $stmt->dim;

        // gets a variable id that *may* contain array keys
        $root_var_id = ExpressionIdentifier::getArrayVarId(
            $root_array_expr,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $parent_var_id = null;

        $offset_already_existed = false;

        $child_stmt = null;

        self::analyzeNestedArrayAssignment(
            $statements_analyzer,
            $codebase,
            $context,
            $assign_value,
            $assignment_type,
            array_reverse($child_stmts),
            $root_var_id,
            $parent_var_id,
            $child_stmt,
            $root_type,
            $current_type,
            $current_dim,
            $offset_already_existed
        );

        $root_is_string = $root_type->isString();
        $key_values = [];

        if ($current_dim instanceof PhpParser\Node\Scalar\String_) {
            $key_values[] = new TLiteralString($current_dim->value);
        } elseif ($current_dim instanceof PhpParser\Node\Scalar\LNumber && !$root_is_string) {
            $key_values[] = new TLiteralInt($current_dim->value);
        } elseif ($current_dim
            && ($key_type = $statements_analyzer->node_data->getType($current_dim))
            && !$root_is_string
        ) {
            $string_literals = $key_type->getLiteralStrings();
            $int_literals = $key_type->getLiteralInts();

            $all_atomic_types = $key_type->getAtomicTypes();

            if (count($string_literals) + count($int_literals) === count($all_atomic_types)) {
                foreach ($string_literals as $string_literal) {
                    $key_values[] = clone $string_literal;
                }

                foreach ($int_literals as $int_literal) {
                    $key_values[] = clone $int_literal;
                }
            }
        }

        if ($key_values) {
            $new_child_type = self::updateTypeWithKeyValues(
                $codebase,
                $root_type,
                $current_type,
                $key_values
            );
        } elseif (!$root_is_string) {
            $new_child_type = self::updateArrayAssignmentChildType(
                $statements_analyzer,
                $codebase,
                $current_dim,
                $context,
                $current_type,
                $root_type,
                $offset_already_existed,
                $child_stmt,
                $parent_var_id
            );
        } else {
            $new_child_type = $root_type;
        }

        $new_child_type->removeType('null');

        if (!$root_type->hasObjectType()) {
            $root_type = $new_child_type;
        }

        $statements_analyzer->node_data->setType($root_array_expr, $root_type);

        if ($root_array_expr instanceof PhpParser\Node\Expr\PropertyFetch) {
            if ($root_array_expr->name instanceof PhpParser\Node\Identifier) {
                InstancePropertyAssignmentAnalyzer::analyze(
                    $statements_analyzer,
                    $root_array_expr,
                    $root_array_expr->name->name,
                    null,
                    $root_type,
                    $context,
                    false
                );
            } else {
                if (ExpressionAnalyzer::analyze($statements_analyzer, $root_array_expr->name, $context) === false) {
                    return false;
                }

                if (ExpressionAnalyzer::analyze($statements_analyzer, $root_array_expr->var, $context) === false) {
                    return false;
                }
            }
        } elseif ($root_array_expr instanceof PhpParser\Node\Expr\StaticPropertyFetch
            && $root_array_expr->name instanceof PhpParser\Node\Identifier
        ) {
            if (StaticPropertyAssignmentAnalyzer::analyze(
                $statements_analyzer,
                $root_array_expr,
                null,
                $root_type,
                $context
            ) === false) {
                return false;
            }
        } elseif ($root_var_id) {
            $context->vars_in_scope[$root_var_id] = $root_type;
        }

        if ($root_array_expr instanceof PhpParser\Node\Expr\MethodCall
            || $root_array_expr instanceof PhpParser\Node\Expr\StaticCall
            || $root_array_expr instanceof PhpParser\Node\Expr\FuncCall
        ) {
            if ($root_type->hasArray()) {
                if (IssueBuffer::accepts(
                    new InvalidArrayAssignment(
                        'Assigning to the output of a function has no effect',
                        new CodeLocation($statements_analyzer->getSource(), $root_array_expr)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )
                ) {
                    // do nothing
                }
            }
        }

        return null;
    }

    /**
     * @param non-empty-list<TLiteralInt|TLiteralString> $key_values
     */
    private static function updateTypeWithKeyValues(
        Codebase $codebase,
        Union $child_stmt_type,
        Union $current_type,
        array $key_values
    ): Union {
        $has_matching_objectlike_property = false;
        $has_matching_string = false;

        $child_stmt_type = clone $child_stmt_type;

        foreach ($child_stmt_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                $type->as = self::updateTypeWithKeyValues(
                    $codebase,
                    $type->as,
                    $current_type,
                    $key_values
                );

                $has_matching_objectlike_property = true;

                $child_stmt_type->substitute(new Union([$type]), $type->as);

                continue;
            }

            foreach ($key_values as $key_value) {
                if ($type instanceof TKeyedArray) {
                    if (isset($type->properties[$key_value->value])) {
                        $has_matching_objectlike_property = true;

                        $type->properties[$key_value->value] = clone $current_type;
                    }
                } elseif ($type instanceof TString
                    && $key_value instanceof TLiteralInt
                ) {
                    $has_matching_string = true;

                    if ($type instanceof TLiteralString
                        && $current_type->isSingleStringLiteral()
                    ) {
                        $new_char = $current_type->getSingleStringLiteral()->value;

                        if (strlen($new_char) === 1) {
                            $type->value[0] = $new_char;
                        }
                    }
                } elseif ($type instanceof TNonEmptyList
                    && $key_value instanceof TLiteralInt
                    && count($key_values) === 1
                ) {
                    $has_matching_objectlike_property = true;

                    $type->type_param = Type::combineUnionTypes(
                        clone $current_type,
                        $type->type_param,
                        $codebase,
                        true,
                        false
                    );
                }
            }
        }

        $child_stmt_type->bustCache();

        if (!$has_matching_objectlike_property && !$has_matching_string) {
            if (count($key_values) === 1) {
                $key_value = $key_values[0];

                $object_like = new TKeyedArray(
                    [$key_value->value => clone $current_type],
                    $key_value instanceof TLiteralClassString
                        ? [$key_value->value => true]
                        : null
                );

                $object_like->sealed = true;

                $array_assignment_type = new Union([
                    $object_like,
                ]);
            } else {
                $array_assignment_literals = $key_values;

                $array_assignment_type = new Union([
                    new TNonEmptyArray([
                        new Union($array_assignment_literals),
                        clone $current_type
                    ])
                ]);
            }

            return Type::combineUnionTypes(
                $child_stmt_type,
                $array_assignment_type,
                $codebase,
                true,
                false
            );
        }

        return $child_stmt_type;
    }

    /**
     * @param list<TLiteralInt|TLiteralString> $key_values $key_values
     */
    private static function taintArrayAssignment(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $expr,
        Union $stmt_type,
        Union $child_stmt_type,
        ?string $var_var_id,
        array $key_values
    ): void {
        if ($statements_analyzer->data_flow_graph
            && ($statements_analyzer->data_flow_graph instanceof VariableUseGraph
                || !in_array('TaintedInput', $statements_analyzer->getSuppressedIssues()))
        ) {
            $var_location = new CodeLocation($statements_analyzer->getSource(), $expr->var);

            $parent_node = DataFlowNode::getForAssignment(
                $var_var_id ?: 'assignment',
                $var_location
            );

            $statements_analyzer->data_flow_graph->addNode($parent_node);

            $old_parent_nodes = $stmt_type->parent_nodes;

            $stmt_type->parent_nodes = [$parent_node->id => $parent_node];

            foreach ($old_parent_nodes as $old_parent_node) {
                $statements_analyzer->data_flow_graph->addPath(
                    $old_parent_node,
                    $parent_node,
                    '='
                );

                if ($stmt_type->by_ref) {
                    $statements_analyzer->data_flow_graph->addPath(
                        $parent_node,
                        $old_parent_node,
                        '='
                    );
                }
            }

            foreach ($stmt_type->parent_nodes as $parent_node) {
                foreach ($child_stmt_type->parent_nodes as $child_parent_node) {
                    if ($key_values) {
                        foreach ($key_values as $key_value) {
                            $statements_analyzer->data_flow_graph->addPath(
                                $child_parent_node,
                                $parent_node,
                                'arrayvalue-assignment-\'' . $key_value->value . '\''
                            );
                        }
                    } else {
                        $statements_analyzer->data_flow_graph->addPath(
                            $child_parent_node,
                            $parent_node,
                            'arrayvalue-assignment'
                        );
                    }
                }
            }
        }
    }

    private static function updateArrayAssignmentChildType(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        ?PhpParser\Node\Expr $current_dim,
        Context $context,
        Union $value_type,
        Union $root_type,
        bool $offset_already_existed,
        ?PhpParser\Node\Expr $child_stmt,
        ?string $parent_var_id
    ): Union {
        $templated_assignment = false;

        if ($current_dim) {
            $key_type = $statements_analyzer->node_data->getType($current_dim);

            if ($key_type) {
                if ($key_type->hasMixed()) {
                    $key_type = Type::getArrayKey();
                }

                if ($key_type->isSingle()) {
                    $key_type_type = $key_type->getSingleAtomic();

                    if ($key_type_type instanceof TDependentListKey
                        && $key_type_type->getVarId() === $parent_var_id
                    ) {
                        $offset_already_existed = true;
                    }

                    if ($key_type_type instanceof TTemplateParam
                        && $key_type_type->as->isSingle()
                        && $root_type->isSingle()
                        && $value_type->isSingle()
                    ) {
                        $key_type_as_type = $key_type_type->as->getSingleAtomic();
                        $value_atomic_type = $value_type->getSingleAtomic();
                        $root_atomic_type = $root_type->getSingleAtomic();

                        if ($key_type_as_type instanceof TTemplateKeyOf
                            && $root_atomic_type instanceof TTemplateParam
                            && $value_atomic_type instanceof TTemplateIndexedAccess
                            && $key_type_as_type->param_name === $root_atomic_type->param_name
                            && $key_type_as_type->defining_class === $root_atomic_type->defining_class
                            && $value_atomic_type->array_param_name === $root_atomic_type->param_name
                            && $value_atomic_type->offset_param_name === $key_type_type->param_name
                            && $value_atomic_type->defining_class === $root_atomic_type->defining_class
                        ) {
                            $templated_assignment = true;
                            $offset_already_existed = true;
                        }
                    }
                }

                $array_atomic_key_type = ArrayFetchAnalyzer::replaceOffsetTypeWithInts(
                    $key_type
                );
            } else {
                $array_atomic_key_type = Type::getArrayKey();
            }

            if ($offset_already_existed
                && $child_stmt
                && $parent_var_id
                && ($parent_type = $context->vars_in_scope[$parent_var_id] ?? null)
            ) {
                if ($parent_type->hasList()) {
                    $array_atomic_type = new TNonEmptyList(
                        $value_type
                    );
                } elseif ($parent_type->hasClassStringMap()
                    && $key_type
                    && $key_type->isTemplatedClassString()
                ) {
                    /**
                     * @var TClassStringMap
                     * @psalm-suppress PossiblyUndefinedStringArrayOffset
                     */
                    $class_string_map = $parent_type->getAtomicTypes()['array'];
                    /**
                     * @var TTemplateParamClass
                     */
                    $offset_type_part = $key_type->getSingleAtomic();

                    $template_result = new TemplateResult(
                        [],
                        [
                            $offset_type_part->param_name => [
                                $offset_type_part->defining_class => new Union([
                                    new TTemplateParam(
                                        $class_string_map->param_name,
                                        $offset_type_part->as_type
                                            ? new Union([$offset_type_part->as_type])
                                            : Type::getObject(),
                                        'class-string-map'
                                    )
                                ])
                            ]
                        ]
                    );

                    TemplateInferredTypeReplacer::replace(
                        $value_type,
                        $template_result,
                        $codebase
                    );

                    $array_atomic_type = new TClassStringMap(
                        $class_string_map->param_name,
                        $class_string_map->as_type,
                        $value_type
                    );
                } else {
                    $array_atomic_type = new TNonEmptyArray([
                        $array_atomic_key_type,
                        $value_type,
                    ]);
                }
            } else {
                $array_atomic_type = new TNonEmptyArray([
                    $array_atomic_key_type,
                    $value_type,
                ]);
            }
        } else {
            $array_atomic_type = new TNonEmptyList($value_type);
        }

        $from_countable_object_like = false;

        $new_child_type = null;

        if (!$current_dim && !$context->inside_loop) {
            $atomic_root_types = $root_type->getAtomicTypes();

            if (isset($atomic_root_types['array'])) {
                if ($array_atomic_type instanceof TClassStringMap) {
                    $array_atomic_type = new TNonEmptyArray([
                        $array_atomic_type->getStandinKeyParam(),
                        $array_atomic_type->value_param
                    ]);
                } elseif ($atomic_root_types['array'] instanceof TNonEmptyArray
                    || $atomic_root_types['array'] instanceof TNonEmptyList
                ) {
                    $array_atomic_type->count = $atomic_root_types['array']->count;
                } elseif ($atomic_root_types['array'] instanceof TKeyedArray
                    && $atomic_root_types['array']->sealed
                ) {
                    $array_atomic_type->count = count($atomic_root_types['array']->properties);
                    $from_countable_object_like = true;

                    if ($atomic_root_types['array']->is_list
                        && $array_atomic_type instanceof TList
                    ) {
                        $array_atomic_type = clone $atomic_root_types['array'];

                        $new_child_type = new Union([$array_atomic_type]);

                        $new_child_type->parent_nodes = $root_type->parent_nodes;
                    }
                } elseif ($array_atomic_type instanceof TList) {
                    $array_atomic_type = new TNonEmptyList(
                        $array_atomic_type->type_param
                    );
                } else {
                    $array_atomic_type = new TNonEmptyArray(
                        $array_atomic_type->type_params
                    );
                }
            }
        }

        $array_assignment_type = new Union([
            $array_atomic_type,
        ]);

        if (!$new_child_type) {
            if ($templated_assignment) {
                $new_child_type = $root_type;
            } else {
                $new_child_type = Type::combineUnionTypes(
                    $root_type,
                    $array_assignment_type,
                    $codebase,
                    true,
                    true
                );
            }
        }

        if ($from_countable_object_like) {
            $atomic_root_types = $new_child_type->getAtomicTypes();

            if (isset($atomic_root_types['array'])
                && ($atomic_root_types['array'] instanceof TNonEmptyArray
                    || $atomic_root_types['array'] instanceof TNonEmptyList)
                && $atomic_root_types['array']->count !== null
            ) {
                $atomic_root_types['array']->count++;
            }
        }

        return $new_child_type;
    }

    /**
     * @param  array<PhpParser\Node\Expr\ArrayDimFetch>  $child_stmts
     */
    private static function analyzeNestedArrayAssignment(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        Context $context,
        ?PhpParser\Node\Expr $assign_value,
        Union $assignment_type,
        array $child_stmts,
        ?string $root_var_id,
        ?string &$parent_var_id,
        ?PhpParser\Node\Expr &$child_stmt,
        Union &$root_type,
        Union &$current_type,
        ?PhpParser\Node\Expr &$current_dim,
        bool &$offset_already_existed
    ): void {
        $reversed_child_stmts = [];
        $var_id_additions = [];
        $full_var_id = true;

        $child_stmt = null;

        // First go from the root element up, and go as far as we can to figure out what
        // array types there are
        while ($child_stmts) {
            $child_stmt = array_shift($child_stmts);

            if (count($child_stmts)) {
                array_unshift($reversed_child_stmts, $child_stmt);
            }

            $child_stmt_dim_type = null;

            $offset_type = null;

            if ($child_stmt->dim) {
                $was_inside_general_use = $context->inside_general_use;
                $context->inside_general_use = true;

                if (ExpressionAnalyzer::analyze(
                    $statements_analyzer,
                    $child_stmt->dim,
                    $context
                ) === false) {
                    $context->inside_general_use = $was_inside_general_use;

                    return;
                }

                $context->inside_general_use = $was_inside_general_use;

                if (!($child_stmt_dim_type = $statements_analyzer->node_data->getType($child_stmt->dim))) {
                    return;
                }

                [$offset_type, $var_id_addition, $full_var_id] = self::getArrayAssignmentOffsetType(
                    $statements_analyzer,
                    $child_stmt,
                    $child_stmt_dim_type
                );

                $var_id_additions[] = $var_id_addition;
            } else {
                $var_id_additions[] = '';
                $full_var_id = false;
            }

            if (!($child_stmt_var_type = $statements_analyzer->node_data->getType($child_stmt->var))) {
                return;
            }

            if ($child_stmt_var_type->isEmpty()) {
                $child_stmt_var_type = Type::getEmptyArray();
                $statements_analyzer->node_data->setType($child_stmt->var, $child_stmt_var_type);
            }

            $array_var_id = $root_var_id . implode('', $var_id_additions);

            if ($parent_var_id && isset($context->vars_in_scope[$parent_var_id])) {
                $child_stmt_var_type = clone $context->vars_in_scope[$parent_var_id];
                $statements_analyzer->node_data->setType($child_stmt->var, $child_stmt_var_type);
            }

            $array_type = clone $child_stmt_var_type;

            $child_stmt_type = ArrayFetchAnalyzer::getArrayAccessTypeGivenOffset(
                $statements_analyzer,
                $child_stmt,
                $array_type,
                $child_stmt_dim_type ?? Type::getInt(),
                true,
                $array_var_id,
                $context,
                $assign_value,
                $child_stmts ? null : $assignment_type
            );

            $statements_analyzer->node_data->setType(
                $child_stmt,
                $child_stmt_type
            );

            $statements_analyzer->node_data->setType($child_stmt->var, $array_type);

            if ($root_var_id) {
                if (!$parent_var_id) {
                    $rooted_parent_id = $root_var_id;
                    $root_type = $array_type;
                } else {
                    $rooted_parent_id = $parent_var_id;
                }

                $context->vars_in_scope[$rooted_parent_id] = $array_type;
                $context->possibly_assigned_var_ids[$rooted_parent_id] = true;
            }

            if (!$child_stmts) {
                // we need this slight hack as the type we're putting it has to be
                // different from the type we're getting out
                if ($array_type->isSingle() && $array_type->hasClassStringMap()) {
                    $assignment_type = $child_stmt_type;
                }

                $child_stmt_type = $assignment_type;
                $statements_analyzer->node_data->setType($child_stmt, $assignment_type);

                if ($statements_analyzer->data_flow_graph) {
                    self::taintArrayAssignment(
                        $statements_analyzer,
                        $child_stmt,
                        $array_type,
                        $assignment_type,
                        ExpressionIdentifier::getArrayVarId(
                            $child_stmt->var,
                            $statements_analyzer->getFQCLN(),
                            $statements_analyzer
                        ),
                        $offset_type !== null ? [$offset_type] : []
                    );
                }
            }

            $current_type = $child_stmt_type;
            $current_dim = $child_stmt->dim;

            $parent_var_id = $array_var_id;
        }

        if ($root_var_id
            && $full_var_id
            && $child_stmt
            && ($child_stmt_var_type = $statements_analyzer->node_data->getType($child_stmt->var))
            && !$child_stmt_var_type->hasObjectType()
        ) {
            $array_var_id = $root_var_id . implode('', $var_id_additions);
            $parent_var_id = $root_var_id . implode('', array_slice($var_id_additions, 0, -1));

            if (isset($context->vars_in_scope[$array_var_id])
                && !$context->vars_in_scope[$array_var_id]->possibly_undefined
            ) {
                $offset_already_existed = true;
            }

            $context->vars_in_scope[$array_var_id] = clone $assignment_type;
            $context->possibly_assigned_var_ids[$array_var_id] = true;
        }

        // only update as many child stmts are we were able to process above
        foreach ($reversed_child_stmts as $child_stmt) {
            $child_stmt_type = $statements_analyzer->node_data->getType($child_stmt);

            if (!$child_stmt_type) {
                throw new InvalidArgumentException('Should never get here');
            }

            $key_values = [];

            if ($current_dim instanceof PhpParser\Node\Scalar\String_) {
                $key_values[] = new TLiteralString($current_dim->value);
            } elseif ($current_dim instanceof PhpParser\Node\Scalar\LNumber) {
                $key_values[] = new TLiteralInt($current_dim->value);
            } elseif ($current_dim
                && ($key_type = $statements_analyzer->node_data->getType($current_dim))
            ) {
                $string_literals = $key_type->getLiteralStrings();
                $int_literals = $key_type->getLiteralInts();

                $all_atomic_types = $key_type->getAtomicTypes();

                if (count($string_literals) + count($int_literals) === count($all_atomic_types)) {
                    foreach ($string_literals as $string_literal) {
                        $key_values[] = clone $string_literal;
                    }

                    foreach ($int_literals as $int_literal) {
                        $key_values[] = clone $int_literal;
                    }
                }
            }

            if ($key_values) {
                $new_child_type = self::updateTypeWithKeyValues(
                    $codebase,
                    $child_stmt_type,
                    $current_type,
                    $key_values
                );
            } else {
                if (!$current_dim) {
                    $array_assignment_type = new Union([
                        new TList($current_type),
                    ]);
                } else {
                    $key_type = $statements_analyzer->node_data->getType($current_dim);

                    $array_assignment_type = new Union([
                        new TArray([
                            $key_type && !$key_type->hasMixed()
                                ? $key_type
                                : Type::getArrayKey(),
                            $current_type,
                        ]),
                    ]);
                }

                $new_child_type = Type::combineUnionTypes(
                    $child_stmt_type,
                    $array_assignment_type,
                    $codebase,
                    true,
                    true
                );
            }

            $new_child_type->removeType('null');
            $new_child_type->possibly_undefined = false;

            if (!$child_stmt_type->hasObjectType()) {
                $child_stmt_type = $new_child_type;
                $statements_analyzer->node_data->setType($child_stmt, $new_child_type);
            }

            $current_type = $child_stmt_type;
            $current_dim = $child_stmt->dim;

            array_pop($var_id_additions);

            $parent_array_var_id = null;

            if ($root_var_id) {
                $array_var_id = $root_var_id . implode('', $var_id_additions);
                $parent_array_var_id = $root_var_id . implode('', array_slice($var_id_additions, 0, -1));
                $context->vars_in_scope[$array_var_id] = clone $child_stmt_type;
                $context->possibly_assigned_var_ids[$array_var_id] = true;
            }

            if ($statements_analyzer->data_flow_graph) {
                self::taintArrayAssignment(
                    $statements_analyzer,
                    $child_stmt,
                    $statements_analyzer->node_data->getType($child_stmt->var) ?? Type::getMixed(),
                    $new_child_type,
                    $parent_array_var_id,
                    $key_values
                );
            }
        }
    }

    /**
     * @return array{TLiteralInt|TLiteralString|null, string, bool}
     */
    private static function getArrayAssignmentOffsetType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $child_stmt,
        Union $child_stmt_dim_type
    ): array {
        if ($child_stmt->dim instanceof PhpParser\Node\Scalar\String_
            || (($child_stmt->dim instanceof PhpParser\Node\Expr\ConstFetch
                    || $child_stmt->dim instanceof PhpParser\Node\Expr\ClassConstFetch)
                && $child_stmt_dim_type->isSingleStringLiteral())
        ) {
            if ($child_stmt->dim instanceof PhpParser\Node\Scalar\String_) {
                $offset_type = new TLiteralString($child_stmt->dim->value);
            } else {
                $offset_type = $child_stmt_dim_type->getSingleStringLiteral();
            }

            if (preg_match('/^(0|[1-9][0-9]*)$/', $offset_type->value)) {
                $var_id_addition = '[' . $offset_type->value . ']';
            } else {
                $var_id_addition = '[\'' . $offset_type->value . '\']';
            }

            return [$offset_type, $var_id_addition, true];
        }

        if ($child_stmt->dim instanceof PhpParser\Node\Scalar\LNumber
            || (($child_stmt->dim instanceof PhpParser\Node\Expr\ConstFetch
                    || $child_stmt->dim instanceof PhpParser\Node\Expr\ClassConstFetch)
                && $child_stmt_dim_type->isSingleIntLiteral())
        ) {
            if ($child_stmt->dim instanceof PhpParser\Node\Scalar\LNumber) {
                $offset_type = new TLiteralInt($child_stmt->dim->value);
            } else {
                $offset_type = $child_stmt_dim_type->getSingleIntLiteral();
            }

            $var_id_addition = '[' . $offset_type->value . ']';

            return [$offset_type, $var_id_addition, true];
        }

        if ($child_stmt->dim instanceof PhpParser\Node\Expr\Variable
            && is_string($child_stmt->dim->name)
        ) {
            $var_id_addition = '[$' . $child_stmt->dim->name . ']';

            return [null, $var_id_addition, true];
        }

        if ($child_stmt->dim instanceof PhpParser\Node\Expr\PropertyFetch
            && $child_stmt->dim->name instanceof PhpParser\Node\Identifier
        ) {
            $object_id = ExpressionIdentifier::getArrayVarId(
                $child_stmt->dim->var,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );

            if ($object_id) {
                $var_id_addition = '[' . $object_id . '->' . $child_stmt->dim->name->name . ']';
            } else {
                $var_id_addition = '[' . $child_stmt_dim_type . ']';
            }

            return [null, $var_id_addition, true];
        }

        if ($child_stmt->dim instanceof PhpParser\Node\Expr\ClassConstFetch
            && $child_stmt->dim->name instanceof PhpParser\Node\Identifier
            && $child_stmt->dim->class instanceof PhpParser\Node\Name
        ) {
            $object_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                $child_stmt->dim->class,
                $statements_analyzer->getAliases()
            );
            $var_id_addition = '[' . $object_name . '::' . $child_stmt->dim->name->name . ']';

            return [null, $var_id_addition, true];
        }

        $var_id_addition = '[' . $child_stmt_dim_type . ']';

        return [null, $var_id_addition, false];
    }
}
