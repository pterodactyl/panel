<?php

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyMixed;
use Psalm\Type\Union;

use function count;

class UnsetAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Unset_ $stmt,
        Context $context
    ): void {
        $context->inside_unset = true;

        foreach ($stmt->vars as $var) {
            $was_inside_general_use = $context->inside_general_use;
            $context->inside_general_use = true;

            ExpressionAnalyzer::analyze($statements_analyzer, $var, $context);

            $context->inside_general_use = $was_inside_general_use;

            $var_id = ExpressionIdentifier::getArrayVarId(
                $var,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );

            if ($var_id) {
                $context->remove($var_id);
            }

            if ($var instanceof PhpParser\Node\Expr\ArrayDimFetch && $var->dim) {
                $root_var_id = ExpressionIdentifier::getArrayVarId(
                    $var->var,
                    $statements_analyzer->getFQCLN(),
                    $statements_analyzer
                );

                if ($root_var_id && isset($context->vars_in_scope[$root_var_id])) {
                    $root_type = clone $context->vars_in_scope[$root_var_id];

                    foreach ($root_type->getAtomicTypes() as $atomic_root_type) {
                        if ($atomic_root_type instanceof TKeyedArray) {
                            if ($var->dim instanceof PhpParser\Node\Scalar\String_
                                || $var->dim instanceof PhpParser\Node\Scalar\LNumber
                            ) {
                                if (isset($atomic_root_type->properties[$var->dim->value])) {
                                    if ($atomic_root_type->is_list
                                        && $var->dim->value !== count($atomic_root_type->properties)-1
                                    ) {
                                        $atomic_root_type->is_list = false;
                                    }
                                    unset($atomic_root_type->properties[$var->dim->value]);
                                    $root_type->bustCache(); //remove id cache
                                }

                                if (!$atomic_root_type->properties) {
                                    if ($atomic_root_type->previous_value_type) {
                                        $root_type->addType(
                                            new TArray([
                                                $atomic_root_type->previous_key_type
                                                    ? clone $atomic_root_type->previous_key_type
                                                    : new Union([new TArrayKey]),
                                                clone $atomic_root_type->previous_value_type,
                                            ])
                                        );
                                    } else {
                                        $root_type->addType(
                                            new TArray([
                                                new Union([new TEmpty]),
                                                new Union([new TEmpty]),
                                            ])
                                        );
                                    }
                                }
                            } else {
                                foreach ($atomic_root_type->properties as $key => $type) {
                                    $atomic_root_type->properties[$key] = clone $type;
                                    $atomic_root_type->properties[$key]->possibly_undefined = true;
                                }

                                $atomic_root_type->sealed = false;

                                $root_type->addType(
                                    $atomic_root_type->getGenericArrayType(false)
                                );

                                $atomic_root_type->is_list = false;
                            }
                        } elseif ($atomic_root_type instanceof TNonEmptyArray) {
                            $root_type->addType(
                                new TArray($atomic_root_type->type_params)
                            );
                        } elseif ($atomic_root_type instanceof TNonEmptyMixed) {
                            $root_type->addType(
                                new TMixed()
                            );
                        } elseif ($atomic_root_type instanceof TList) {
                            $root_type->addType(
                                new TArray([
                                    Type::getInt(),
                                    $atomic_root_type->type_param
                                ])
                            );
                        }
                    }

                    $context->vars_in_scope[$root_var_id] = $root_type;

                    $context->removeVarFromConflictingClauses(
                        $root_var_id,
                        $context->vars_in_scope[$root_var_id],
                        $statements_analyzer
                    );
                }
            }
        }

        $context->inside_unset = false;
    }
}
