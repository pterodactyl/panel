<?php

namespace Psalm\Internal\PhpVisitor;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;

/**
 * @internal
 *
 * This produces a graph of probably assignments inside a loop
 *
 * With this map we can calculate how many times the loop analysis must
 * be run before all variables have the correct types
 */
class AssignmentMapVisitor extends PhpParser\NodeVisitorAbstract
{
    /**
     * @var array<string, array<string, bool>>
     */
    protected $assignment_map = [];

    /**
     * @var string|null
     */
    protected $this_class_name;

    public function __construct(?string $this_class_name)
    {
        $this->this_class_name = $this_class_name;
    }

    public function enterNode(PhpParser\Node $node): ?int
    {
        if ($node instanceof PhpParser\Node\Expr\Assign) {
            $right_var_id = ExpressionIdentifier::getRootVarId($node->expr, $this->this_class_name);

            if ($node->var instanceof PhpParser\Node\Expr\List_
                || $node->var instanceof PhpParser\Node\Expr\Array_
            ) {
                foreach ($node->var->items as $assign_item) {
                    if ($assign_item) {
                        $left_var_id = ExpressionIdentifier::getRootVarId($assign_item->value, $this->this_class_name);

                        if ($left_var_id) {
                            $this->assignment_map[$left_var_id][$right_var_id ?: 'isset'] = true;
                        }
                    }
                }
            } else {
                $left_var_id = ExpressionIdentifier::getRootVarId($node->var, $this->this_class_name);

                if ($left_var_id) {
                    $this->assignment_map[$left_var_id][$right_var_id ?: 'isset'] = true;
                }
            }

            return PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof PhpParser\Node\Expr\PostInc
            || $node instanceof PhpParser\Node\Expr\PostDec
            || $node instanceof PhpParser\Node\Expr\PreInc
            || $node instanceof PhpParser\Node\Expr\PreDec
            || $node instanceof PhpParser\Node\Expr\AssignOp
        ) {
            $var_id = ExpressionIdentifier::getRootVarId($node->var, $this->this_class_name);

            if ($var_id) {
                $this->assignment_map[$var_id][$var_id] = true;
            }

            return PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof PhpParser\Node\Expr\FuncCall
            || $node instanceof PhpParser\Node\Expr\MethodCall
            || $node instanceof PhpParser\Node\Expr\StaticCall
        ) {
            if (!$node->isFirstClassCallable()) {
                foreach ($node->getArgs() as $arg) {
                    $arg_var_id = ExpressionIdentifier::getRootVarId($arg->value, $this->this_class_name);

                    if ($arg_var_id) {
                        $this->assignment_map[$arg_var_id][$arg_var_id] = true;
                    }
                }
            }

            if ($node instanceof PhpParser\Node\Expr\MethodCall) {
                $var_id = ExpressionIdentifier::getRootVarId($node->var, $this->this_class_name);

                if ($var_id) {
                    $this->assignment_map[$var_id]['isset'] = true;
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Unset_) {
            foreach ($node->vars as $arg) {
                $arg_var_id = ExpressionIdentifier::getRootVarId($arg, $this->this_class_name);

                if ($arg_var_id) {
                    $this->assignment_map[$arg_var_id][$arg_var_id] = true;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getAssignmentMap(): array
    {
        return $this->assignment_map;
    }
}
