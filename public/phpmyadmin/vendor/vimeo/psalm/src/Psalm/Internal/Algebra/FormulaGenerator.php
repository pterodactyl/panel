<?php

namespace Psalm\Internal\Algebra;

use PhpParser;
use Psalm\Codebase;
use Psalm\FileSource;
use Psalm\Internal\Algebra;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Node\Expr\BinaryOp\VirtualBooleanAnd;
use Psalm\Node\Expr\BinaryOp\VirtualBooleanOr;
use Psalm\Node\Expr\VirtualBooleanNot;

use function array_merge;
use function count;
use function spl_object_id;
use function strlen;
use function substr;

class FormulaGenerator
{
     /**
     * @return list<Clause>
     */
    public static function getFormula(
        int $conditional_object_id,
        int $creating_object_id,
        PhpParser\Node\Expr $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase = null,
        bool $inside_negation = false,
        bool $cache = true
    ): array {
        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
        ) {
            $left_assertions = self::getFormula(
                $conditional_object_id,
                spl_object_id($conditional->left),
                $conditional->left,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );

            $right_assertions = self::getFormula(
                $conditional_object_id,
                spl_object_id($conditional->right),
                $conditional->right,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );

            return array_merge(
                $left_assertions,
                $right_assertions
            );
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
        ) {
            $left_clauses = self::getFormula(
                $conditional_object_id,
                spl_object_id($conditional->left),
                $conditional->left,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );

            $right_clauses = self::getFormula(
                $conditional_object_id,
                spl_object_id($conditional->right),
                $conditional->right,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );

            return Algebra::combineOredClauses($left_clauses, $right_clauses, $conditional_object_id);
        }

        if ($conditional instanceof PhpParser\Node\Expr\BooleanNot) {
            if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
                $and_expr = new VirtualBooleanAnd(
                    new VirtualBooleanNot(
                        $conditional->expr->left,
                        $conditional->getAttributes()
                    ),
                    new VirtualBooleanNot(
                        $conditional->expr->right,
                        $conditional->getAttributes()
                    ),
                    $conditional->expr->getAttributes()
                );

                return self::getFormula(
                    $conditional_object_id,
                    $conditional_object_id,
                    $and_expr,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    false
                );
            }

            if ($conditional->expr instanceof PhpParser\Node\Expr\Isset_
                && count($conditional->expr->vars) > 1
            ) {
                $anded_assertions = null;

                if ($cache && $source instanceof StatementsAnalyzer) {
                    $anded_assertions = $source->node_data->getAssertions($conditional->expr);
                }

                if ($anded_assertions === null) {
                    $anded_assertions = AssertionFinder::scrapeAssertions(
                        $conditional->expr,
                        $this_class_name,
                        $source,
                        $codebase,
                        $inside_negation,
                        $cache
                    );

                    if ($cache && $source instanceof StatementsAnalyzer) {
                        $source->node_data->setAssertions($conditional->expr, $anded_assertions);
                    }
                }

                $clauses = [];

                foreach ($anded_assertions as $assertions) {
                    foreach ($assertions as $var => $anded_types) {
                        $redefined = false;

                        if ($var[0] === '=') {
                            /** @var string */
                            $var = substr($var, 1);
                            $redefined = true;
                        }

                        foreach ($anded_types as $orred_types) {
                            $clauses[] = new Clause(
                                [$var => $orred_types],
                                $conditional_object_id,
                                spl_object_id($conditional->expr),
                                false,
                                true,
                                $orred_types[0][0] === '='
                                    || $orred_types[0][0] === '~'
                                    || (strlen($orred_types[0]) > 1
                                        && ($orred_types[0][1] === '='
                                            || $orred_types[0][1] === '~')),
                                $redefined ? [$var => true] : []
                            );
                        }
                    }
                }

                return Algebra::negateFormula($clauses);
            }

            if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
                $and_expr = new VirtualBooleanOr(
                    new VirtualBooleanNot(
                        $conditional->expr->left,
                        $conditional->getAttributes()
                    ),
                    new VirtualBooleanNot(
                        $conditional->expr->right,
                        $conditional->getAttributes()
                    ),
                    $conditional->expr->getAttributes()
                );

                return self::getFormula(
                    $conditional_object_id,
                    spl_object_id($conditional->expr),
                    $and_expr,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    false
                );
            }

            return Algebra::negateFormula(
                self::getFormula(
                    $conditional_object_id,
                    spl_object_id($conditional->expr),
                    $conditional->expr,
                    $this_class_name,
                    $source,
                    $codebase,
                    !$inside_negation
                )
            );
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
        ) {
            $false_pos = AssertionFinder::hasFalseVariable($conditional);
            $true_pos = AssertionFinder::hasTrueVariable($conditional);

            if ($false_pos === AssertionFinder::ASSIGNMENT_TO_RIGHT
                && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                    || $conditional->left instanceof PhpParser\Node\Expr\BooleanNot)
            ) {
                return Algebra::negateFormula(
                    self::getFormula(
                        $conditional_object_id,
                        spl_object_id($conditional->left),
                        $conditional->left,
                        $this_class_name,
                        $source,
                        $codebase,
                        !$inside_negation,
                        $cache
                    )
                );
            }

            if ($false_pos === AssertionFinder::ASSIGNMENT_TO_LEFT
                && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                    || $conditional->right instanceof PhpParser\Node\Expr\BooleanNot)
            ) {
                return Algebra::negateFormula(
                    self::getFormula(
                        $conditional_object_id,
                        spl_object_id($conditional->right),
                        $conditional->right,
                        $this_class_name,
                        $source,
                        $codebase,
                        !$inside_negation,
                        $cache
                    )
                );
            }

            if ($true_pos === AssertionFinder::ASSIGNMENT_TO_RIGHT
                && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                    || $conditional->left instanceof PhpParser\Node\Expr\BooleanNot)
            ) {
                return self::getFormula(
                    $conditional_object_id,
                    spl_object_id($conditional->left),
                    $conditional->left,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache
                );
            }

            if ($true_pos === AssertionFinder::ASSIGNMENT_TO_LEFT
                && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                    || $conditional->right instanceof PhpParser\Node\Expr\BooleanNot)
            ) {
                return self::getFormula(
                    $conditional_object_id,
                    spl_object_id($conditional->right),
                    $conditional->right,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache
                );
            }
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
        ) {
            $false_pos = AssertionFinder::hasFalseVariable($conditional);
            $true_pos = AssertionFinder::hasTrueVariable($conditional);

            if ($true_pos === AssertionFinder::ASSIGNMENT_TO_RIGHT
                && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                    || $conditional->left instanceof PhpParser\Node\Expr\BooleanNot)
            ) {
                return Algebra::negateFormula(
                    self::getFormula(
                        $conditional_object_id,
                        spl_object_id($conditional->left),
                        $conditional->left,
                        $this_class_name,
                        $source,
                        $codebase,
                        !$inside_negation,
                        $cache
                    )
                );
            }

            if ($true_pos === AssertionFinder::ASSIGNMENT_TO_LEFT
                && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                    || $conditional->right instanceof PhpParser\Node\Expr\BooleanNot)
            ) {
                return Algebra::negateFormula(
                    self::getFormula(
                        $conditional_object_id,
                        spl_object_id($conditional->right),
                        $conditional->right,
                        $this_class_name,
                        $source,
                        $codebase,
                        !$inside_negation,
                        $cache
                    )
                );
            }

            if ($false_pos === AssertionFinder::ASSIGNMENT_TO_RIGHT
                && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                    || $conditional->left instanceof PhpParser\Node\Expr\BooleanNot)
            ) {
                return self::getFormula(
                    $conditional_object_id,
                    spl_object_id($conditional->left),
                    $conditional->left,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache
                );
            }

            if ($false_pos === AssertionFinder::ASSIGNMENT_TO_LEFT
                && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                    || $conditional->right instanceof PhpParser\Node\Expr\BooleanNot)
            ) {
                return self::getFormula(
                    $conditional_object_id,
                    spl_object_id($conditional->right),
                    $conditional->right,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache
                );
            }
        }

        if ($conditional instanceof PhpParser\Node\Expr\Cast\Bool_) {
            return self::getFormula(
                $conditional_object_id,
                spl_object_id($conditional->expr),
                $conditional->expr,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );
        }

        $anded_assertions = null;

        if ($cache && $source instanceof StatementsAnalyzer) {
            $anded_assertions = $source->node_data->getAssertions($conditional);
        }

        if ($anded_assertions === null) {
            $anded_assertions = AssertionFinder::scrapeAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );

            if ($cache && $source instanceof StatementsAnalyzer) {
                $source->node_data->setAssertions($conditional, $anded_assertions);
            }
        }

        $clauses = [];

        foreach ($anded_assertions as $assertions) {
            foreach ($assertions as $var => $anded_types) {
                $redefined = false;

                if ($var[0] === '=') {
                    /** @var string */
                    $var = substr($var, 1);
                    $redefined = true;
                }

                foreach ($anded_types as $orred_types) {
                    $clauses[] = new Clause(
                        [$var => $orred_types],
                        $conditional_object_id,
                        $creating_object_id,
                        false,
                        true,
                        $orred_types[0][0] === '='
                            || $orred_types[0][0] === '~'
                            || (strlen($orred_types[0]) > 1
                                && ($orred_types[0][1] === '='
                                    || $orred_types[0][1] === '~')),
                        $redefined ? [$var => true] : []
                    );
                }
            }
        }

        if ($clauses) {
            return $clauses;
        }

        /** @psalm-suppress MixedOperand */
        $conditional_ref = '*' . $conditional->getAttribute('startFilePos')
            . ':' . $conditional->getAttribute('endFilePos');

        return [new Clause([$conditional_ref => ['!falsy']], $conditional_object_id, $creating_object_id)];
    }
}
