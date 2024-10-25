<?php

namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\NodeTypeProvider;

use function array_diff;
use function array_filter;
use function array_intersect;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function strtolower;

/**
 * @internal
 */
class ScopeAnalyzer
{
    public const ACTION_END = 'END';
    public const ACTION_BREAK = 'BREAK';
    public const ACTION_CONTINUE = 'CONTINUE';
    public const ACTION_LEAVE_SWITCH = 'LEAVE_SWITCH';
    public const ACTION_NONE = 'NONE';
    public const ACTION_RETURN = 'RETURN';

    /**
     * @param array<PhpParser\Node\Stmt>   $stmts
     *
     */
    public static function doesEverBreak(array $stmts): bool
    {
        if (empty($stmts)) {
            return false;
        }

        for ($i = count($stmts) - 1; $i >= 0; --$i) {
            $stmt = $stmts[$i];

            if ($stmt instanceof PhpParser\Node\Stmt\Break_) {
                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                if (self::doesEverBreak($stmt->stmts)) {
                    return true;
                }

                if ($stmt->else && self::doesEverBreak($stmt->else->stmts)) {
                    return true;
                }

                foreach ($stmt->elseifs as $elseif) {
                    if (self::doesEverBreak($elseif->stmts)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param array<PhpParser\Node> $stmts
     * @param array<lowercase-string, bool> $exit_functions
     * @param list<'loop'|'switch'> $break_types
     * @param bool $return_is_exit Exit and Throw statements are treated differently from return if this is false
     *
     * @return list<self::ACTION_*>
     *
     * @psalm-suppress ComplexMethod nothing much we can do
     */
    public static function getControlActions(
        array $stmts,
        ?NodeDataProvider $nodes,
        array $exit_functions,
        array $break_types,
        bool $return_is_exit = true
    ): array {
        if (empty($stmts)) {
            return [self::ACTION_NONE];
        }

        $control_actions = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Return_ ||
                $stmt instanceof PhpParser\Node\Stmt\Throw_ ||
                ($stmt instanceof PhpParser\Node\Stmt\Expression && $stmt->expr instanceof PhpParser\Node\Expr\Exit_)
            ) {
                if (!$return_is_exit && $stmt instanceof PhpParser\Node\Stmt\Return_) {
                    $stmt_return_type = null;
                    if ($nodes && $stmt->expr) {
                        $stmt_return_type = $nodes->getType($stmt->expr);
                    }

                    // don't consider a return if the expression never returns (e.g. a throw inside a short closure)
                    if ($stmt_return_type && ($stmt_return_type->isNever() || $stmt_return_type->isEmpty())) {
                        return array_values(array_unique(array_merge($control_actions, [self::ACTION_END])));
                    }

                    return array_values(array_unique(array_merge($control_actions, [self::ACTION_RETURN])));
                }

                return array_values(array_unique(array_merge($control_actions, [self::ACTION_END])));
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Expression) {
                // This allows calls to functions that always exit to act as exit statements themselves
                if ($nodes
                    && ($stmt_expr_type = $nodes->getType($stmt->expr))
                    && $stmt_expr_type->isNever()
                ) {
                    return array_values(array_unique(array_merge($control_actions, [self::ACTION_END])));
                }

                if ($exit_functions) {
                    if ($stmt->expr instanceof PhpParser\Node\Expr\FuncCall
                        || $stmt->expr instanceof PhpParser\Node\Expr\StaticCall
                    ) {
                        if ($stmt->expr instanceof PhpParser\Node\Expr\FuncCall) {
                            /** @var string|null */
                            $resolved_name = $stmt->expr->name->getAttribute('resolvedName');

                            if ($resolved_name && isset($exit_functions[strtolower($resolved_name)])) {
                                return array_values(array_unique(array_merge($control_actions, [self::ACTION_END])));
                            }
                        } elseif ($stmt->expr->class instanceof PhpParser\Node\Name
                            && $stmt->expr->name instanceof PhpParser\Node\Identifier
                        ) {
                            /** @var string|null */
                            $resolved_class_name = $stmt->expr->class->getAttribute('resolvedName');

                            if ($resolved_class_name
                                && isset($exit_functions[strtolower($resolved_class_name . '::' . $stmt->expr->name)])
                            ) {
                                return array_values(array_unique(array_merge($control_actions, [self::ACTION_END])));
                            }
                        }
                    }
                }

                continue;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Continue_) {
                $count = !$stmt->num
                    ? 1
                    : ($stmt->num instanceof PhpParser\Node\Scalar\LNumber ? $stmt->num->value : null);

                if ($break_types && $count !== null && count($break_types) >= $count) {
                    if ($break_types[count($break_types) - $count] === 'switch') {
                        return array_merge($control_actions, [self::ACTION_LEAVE_SWITCH]);
                    }

                    return array_values($control_actions);
                }

                return array_values(array_unique(array_merge($control_actions, [self::ACTION_CONTINUE])));
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Break_) {
                $count = !$stmt->num
                    ? 1
                    : ($stmt->num instanceof PhpParser\Node\Scalar\LNumber ? $stmt->num->value : null);

                if ($break_types && $count !== null && count($break_types) >= $count) {
                    if ($break_types[count($break_types) - $count] === 'switch') {
                        return array_merge($control_actions, [self::ACTION_LEAVE_SWITCH]);
                    }

                    return array_values($control_actions);
                }

                return array_values(array_unique(array_merge($control_actions, [self::ACTION_BREAK])));
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                $if_statement_actions = self::getControlActions(
                    $stmt->stmts,
                    $nodes,
                    $exit_functions,
                    $break_types,
                    $return_is_exit
                );

                $all_leave = !array_filter(
                    $if_statement_actions,
                    function ($action) {
                        return $action === self::ACTION_NONE;
                    }
                );

                $else_statement_actions = $stmt->else
                    ? self::getControlActions(
                        $stmt->else->stmts,
                        $nodes,
                        $exit_functions,
                        $break_types,
                        $return_is_exit
                    ) : [];

                $all_leave = $all_leave
                    && $else_statement_actions
                    && !array_filter(
                        $else_statement_actions,
                        function ($action) {
                            return $action === self::ACTION_NONE;
                        }
                    );

                $all_elseif_actions = [];

                if ($stmt->elseifs) {
                    foreach ($stmt->elseifs as $elseif) {
                        $elseif_control_actions = self::getControlActions(
                            $elseif->stmts,
                            $nodes,
                            $exit_functions,
                            $break_types,
                            $return_is_exit
                        );

                        $all_leave = $all_leave
                            && !array_filter(
                                $elseif_control_actions,
                                function ($action) {
                                    return $action === self::ACTION_NONE;
                                }
                            );

                        $all_elseif_actions = array_merge($elseif_control_actions, $all_elseif_actions);
                    }
                }

                if ($all_leave) {
                    return array_values(
                        array_unique(
                            array_merge(
                                $control_actions,
                                $if_statement_actions,
                                $else_statement_actions,
                                $all_elseif_actions
                            )
                        )
                    );
                }

                $control_actions = array_filter(
                    array_merge(
                        $control_actions,
                        $if_statement_actions,
                        $else_statement_actions,
                        $all_elseif_actions
                    ),
                    function ($action) {
                        return $action !== self::ACTION_NONE;
                    }
                );
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                $has_ended = false;
                $has_non_breaking_default = false;
                $has_default_terminator = false;

                $all_case_actions = [];

                // iterate backwards in a case statement
                for ($d = count($stmt->cases) - 1; $d >= 0; --$d) {
                    $case = $stmt->cases[$d];

                    $case_actions = self::getControlActions(
                        $case->stmts,
                        $nodes,
                        $exit_functions,
                        array_merge($break_types, ['switch']),
                        $return_is_exit
                    );

                    if (array_intersect([
                        self::ACTION_LEAVE_SWITCH,
                        self::ACTION_BREAK,
                        self::ACTION_CONTINUE
                    ], $case_actions)
                    ) {
                        continue 2;
                    }

                    if (!$case->cond) {
                        $has_non_breaking_default = true;
                    }

                    $case_does_end = !array_diff(
                        $control_actions,
                        [self::ACTION_END, self::ACTION_RETURN]
                    );

                    if ($case_does_end) {
                        $has_ended = true;
                    }

                    $all_case_actions = array_merge(
                        $all_case_actions,
                        $case_actions
                    );

                    if (!$case_does_end && !$has_ended) {
                        continue 2;
                    }

                    if ($has_non_breaking_default && $case_does_end) {
                        $has_default_terminator = true;
                    }
                }

                $all_case_actions = array_filter(
                    $all_case_actions,
                    function ($action) {
                        return $action !== self::ACTION_NONE;
                    }
                );

                if ($has_default_terminator || $stmt->getAttribute('allMatched', false)) {
                    return array_values(array_unique(array_merge($control_actions, $all_case_actions)));
                }

                $control_actions = array_merge(
                    $control_actions,
                    $all_case_actions
                );
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Do_
                || $stmt instanceof PhpParser\Node\Stmt\While_
                || $stmt instanceof PhpParser\Node\Stmt\Foreach_
                || $stmt instanceof PhpParser\Node\Stmt\For_
            ) {
                $loop_actions = self::getControlActions(
                    $stmt->stmts,
                    $nodes,
                    $exit_functions,
                    array_merge($break_types, ['loop']),
                    $return_is_exit
                );

                $control_actions = array_filter(
                    array_merge($control_actions, $loop_actions),
                    function ($action) {
                        return $action !== self::ACTION_NONE;
                    }
                );

                if ($stmt instanceof PhpParser\Node\Stmt\While_
                    && $nodes
                    && ($stmt_expr_type = $nodes->getType($stmt->cond))
                    && $stmt_expr_type->isAlwaysTruthy()
                ) {
                    //infinite while loop that only return don't have an exit path
                    $have_exit_path = (bool)array_diff(
                        $control_actions,
                        [self::ACTION_END, self::ACTION_RETURN]
                    );

                    if (!$have_exit_path) {
                        return array_values(array_unique($control_actions));
                    }
                }

                if ($stmt instanceof PhpParser\Node\Stmt\For_
                    && $nodes
                ) {
                    $is_infinite_loop = true;
                    if ($stmt->cond) {
                        foreach ($stmt->cond as $cond) {
                            $stmt_expr_type = $nodes->getType($cond);
                            if (!$stmt_expr_type || !$stmt_expr_type->isAlwaysTruthy()) {
                                $is_infinite_loop = false;
                            }
                        }
                    }

                    if ($is_infinite_loop) {
                        //infinite while loop that only return don't have an exit path
                        $have_exit_path = (bool)array_diff(
                            $control_actions,
                            [self::ACTION_END, self::ACTION_RETURN]
                        );

                        if (!$have_exit_path) {
                            return array_values(array_unique($control_actions));
                        }
                    }
                }
            }

            if ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                $try_statement_actions = self::getControlActions(
                    $stmt->stmts,
                    $nodes,
                    $exit_functions,
                    $break_types,
                    $return_is_exit
                );

                $try_leaves = !array_filter(
                    $try_statement_actions,
                    function ($action) {
                        return $action === self::ACTION_NONE;
                    }
                );

                $all_catch_actions = [];

                if ($stmt->catches) {
                    $all_leave = $try_leaves;

                    foreach ($stmt->catches as $catch) {
                        $catch_actions = self::getControlActions(
                            $catch->stmts,
                            $nodes,
                            $exit_functions,
                            $break_types,
                            $return_is_exit
                        );

                        $all_leave = $all_leave
                            && !array_filter(
                                $catch_actions,
                                function ($action) {
                                    return $action === self::ACTION_NONE;
                                }
                            );

                        if (!$all_leave) {
                            $control_actions = array_merge($control_actions, $catch_actions);
                        } else {
                            $all_catch_actions = array_merge($all_catch_actions, $catch_actions);
                        }
                    }

                    if ($all_leave && $try_statement_actions !== [self::ACTION_NONE]) {
                        return array_values(
                            array_unique(
                                array_merge(
                                    $control_actions,
                                    $try_statement_actions,
                                    $all_catch_actions
                                )
                            )
                        );
                    }
                } elseif ($try_leaves) {
                    return array_values(array_unique(array_merge($control_actions, $try_statement_actions)));
                }

                if ($stmt->finally && $stmt->finally->stmts) {
                    $finally_statement_actions = self::getControlActions(
                        $stmt->finally->stmts,
                        $nodes,
                        $exit_functions,
                        $break_types,
                        $return_is_exit
                    );

                    if (!in_array(self::ACTION_NONE, $finally_statement_actions, true)) {
                        return array_merge(
                            array_filter(
                                $control_actions,
                                function ($action) {
                                    return $action !== self::ACTION_NONE;
                                }
                            ),
                            $finally_statement_actions
                        );
                    }
                }

                $control_actions = array_filter(
                    array_merge($control_actions, $try_statement_actions),
                    function ($action) {
                        return $action !== self::ACTION_NONE;
                    }
                );
            }
        }

        $control_actions[] = self::ACTION_NONE;

        return array_values(array_unique($control_actions));
    }

    /**
     * @param   array<PhpParser\Node> $stmts
     *
     */
    public static function onlyThrowsOrExits(NodeTypeProvider $type_provider, array $stmts): bool
    {
        if (empty($stmts)) {
            return false;
        }

        for ($i = count($stmts) - 1; $i >= 0; --$i) {
            $stmt = $stmts[$i];

            if ($stmt instanceof PhpParser\Node\Stmt\Throw_
                || ($stmt instanceof PhpParser\Node\Stmt\Expression
                    && $stmt->expr instanceof PhpParser\Node\Expr\Exit_)
            ) {
                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Expression) {
                $stmt_type = $type_provider->getType($stmt->expr);

                if ($stmt_type && $stmt_type->isNever()) {
                    return true;
                }
            }
        }

        return false;
    }
}
