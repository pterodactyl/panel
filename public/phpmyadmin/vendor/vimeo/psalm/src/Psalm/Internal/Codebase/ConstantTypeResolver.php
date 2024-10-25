<?php

namespace Psalm\Internal\Codebase;

use Psalm\Exception\CircularReferenceException;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Scanner\UnresolvedConstant\ArrayOffsetFetch;
use Psalm\Internal\Scanner\UnresolvedConstant\ArraySpread;
use Psalm\Internal\Scanner\UnresolvedConstant\ArrayValue;
use Psalm\Internal\Scanner\UnresolvedConstant\ClassConstant;
use Psalm\Internal\Scanner\UnresolvedConstant\Constant;
use Psalm\Internal\Scanner\UnresolvedConstant\ScalarValue;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedAdditionOp;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedBinaryOp;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedBitwiseAnd;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedBitwiseOr;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedBitwiseXor;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedConcatOp;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedDivisionOp;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedMultiplicationOp;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedSubtractionOp;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedTernary;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;
use ReflectionProperty;

use function ctype_digit;
use function is_float;
use function is_int;
use function is_string;
use function spl_object_id;

/**
 * @internal
 */
class ConstantTypeResolver
{
    public static function resolve(
        ClassLikes $classlikes,
        UnresolvedConstantComponent $c,
        StatementsAnalyzer $statements_analyzer = null,
        array $visited_constant_ids = []
    ): Atomic {
        $c_id = spl_object_id($c);

        if (isset($visited_constant_ids[$c_id])) {
            throw new CircularReferenceException('Found a circular reference');
        }

        if ($c instanceof ScalarValue) {
            return self::getLiteralTypeFromScalarValue($c->value);
        }

        if ($c instanceof UnresolvedBinaryOp) {
            $left = self::resolve(
                $classlikes,
                $c->left,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );
            $right = self::resolve(
                $classlikes,
                $c->right,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );

            if ($left instanceof TMixed || $right instanceof TMixed) {
                return new TMixed;
            }

            if ($c instanceof UnresolvedConcatOp) {
                if (($left instanceof TLiteralString
                        || $left instanceof TLiteralFloat
                        || $left instanceof TLiteralInt)
                    && ($right instanceof TLiteralString
                        || $right instanceof TLiteralFloat
                        || $right instanceof TLiteralInt)
                ) {
                    return new TLiteralString($left->value . $right->value);
                }

                return new TString();
            }

            if ($c instanceof UnresolvedAdditionOp
                || $c instanceof UnresolvedSubtractionOp
                || $c instanceof UnresolvedDivisionOp
                || $c instanceof UnresolvedMultiplicationOp
                || $c instanceof UnresolvedBitwiseOr
                || $c instanceof UnresolvedBitwiseXor
                || $c instanceof UnresolvedBitwiseAnd
            ) {
                if (($left instanceof TLiteralFloat || $left instanceof TLiteralInt)
                    && ($right instanceof TLiteralFloat || $right instanceof TLiteralInt)
                ) {
                    if ($c instanceof UnresolvedAdditionOp) {
                        return self::getLiteralTypeFromScalarValue($left->value + $right->value);
                    }

                    if ($c instanceof UnresolvedSubtractionOp) {
                        return self::getLiteralTypeFromScalarValue($left->value - $right->value);
                    }

                    if ($c instanceof UnresolvedDivisionOp) {
                        return self::getLiteralTypeFromScalarValue($left->value / $right->value);
                    }

                    if ($c instanceof UnresolvedBitwiseOr) {
                        return self::getLiteralTypeFromScalarValue($left->value | $right->value);
                    }

                    if ($c instanceof UnresolvedBitwiseXor) {
                        return self::getLiteralTypeFromScalarValue($left->value ^ $right->value);
                    }

                    if ($c instanceof UnresolvedBitwiseAnd) {
                        return self::getLiteralTypeFromScalarValue($left->value & $right->value);
                    }

                    return self::getLiteralTypeFromScalarValue($left->value * $right->value);
                }

                if ($left instanceof TKeyedArray && $right instanceof TKeyedArray) {
                    $keyed_array = new TKeyedArray($left->properties + $right->properties);
                    $keyed_array->sealed = true;
                    return $keyed_array;
                }

                return new TMixed;
            }

            return new TMixed;
        }

        if ($c instanceof UnresolvedTernary) {
            $cond = self::resolve(
                $classlikes,
                $c->cond,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );
            $if = $c->if ? self::resolve(
                $classlikes,
                $c->if,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            ) : null;
            $else = self::resolve(
                $classlikes,
                $c->else,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );

            if ($cond instanceof TLiteralFloat
                || $cond instanceof TLiteralInt
                || $cond instanceof TLiteralString
            ) {
                if ($cond->value) {
                    return $if ?? $cond;
                }
            } elseif ($cond instanceof TFalse || $cond instanceof TNull) {
                return $else;
            } elseif ($cond instanceof TTrue) {
                return $if ?? $cond;
            }
        }

        if ($c instanceof ArrayValue) {
            $properties = [];
            $auto_key = 0;

            if (!$c->entries) {
                return new TArray([Type::getEmpty(), Type::getEmpty()]);
            }

            $is_list = true;

            foreach ($c->entries as $entry) {
                if ($entry instanceof ArraySpread) {
                    $spread_array = self::resolve(
                        $classlikes,
                        $entry->array,
                        $statements_analyzer,
                        $visited_constant_ids + [$c_id => true]
                    );

                    if ($spread_array instanceof TArray && $spread_array->type_params[1]->isEmpty()) {
                        continue;
                    }

                    if (!$spread_array instanceof TKeyedArray) {
                        return new TArray([Type::getArrayKey(), Type::getMixed()]);
                    }

                    foreach ($spread_array->properties as $spread_array_type) {
                        $properties[$auto_key++] = $spread_array_type;
                    }
                    continue;
                }

                if ($entry->key) {
                    $key_type = self::resolve(
                        $classlikes,
                        $entry->key,
                        $statements_analyzer,
                        $visited_constant_ids + [$c_id => true]
                    );

                    if (!$key_type instanceof TLiteralInt
                        || $key_type->value !== $auto_key
                    ) {
                        $is_list = false;
                    }
                } else {
                    $key_type = new TLiteralInt($auto_key);
                }

                if ($key_type instanceof TLiteralInt
                    || $key_type instanceof TLiteralString
                ) {
                    $key_value = $key_type->value;
                    if ($key_type instanceof TLiteralInt) {
                        $auto_key = $key_type->value + 1;
                    } elseif (ctype_digit($key_type->value)) {
                        $auto_key = ((int) $key_type->value) + 1;
                    }
                } else {
                    return new TArray([Type::getArrayKey(), Type::getMixed()]);
                }

                $value_type = new Union([self::resolve(
                    $classlikes,
                    $entry->value,
                    $statements_analyzer,
                    $visited_constant_ids + [$c_id => true]
                )]);

                $properties[$key_value] = $value_type;
            }

            if (empty($properties)) {
                $resolved_type = new TArray([
                    new Union([new TEmpty()]),
                    new Union([new TEmpty()]),
                ]);
            } else {
                $resolved_type = new TKeyedArray($properties);

                $resolved_type->is_list = $is_list;
                $resolved_type->sealed = true;
            }

            return $resolved_type;
        }

        if ($c instanceof ClassConstant) {
            if ($c->name === 'class') {
                return new TLiteralClassString($c->fqcln);
            }

            $found_type = $classlikes->getClassConstantType(
                $c->fqcln,
                $c->name,
                ReflectionProperty::IS_PRIVATE,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );

            if ($found_type) {
                return $found_type->getSingleAtomic();
            }
        }

        if ($c instanceof ArrayOffsetFetch) {
            $var_type = self::resolve(
                $classlikes,
                $c->array,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );

            $offset_type = self::resolve(
                $classlikes,
                $c->offset,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );

            if ($var_type instanceof TKeyedArray
                && ($offset_type instanceof TLiteralInt
                    || $offset_type instanceof TLiteralString)
            ) {
                $union = $var_type->properties[$offset_type->value] ?? null;

                if ($union && $union->isSingle()) {
                    return $union->getSingleAtomic();
                }
            }
        }

        if ($c instanceof Constant) {
            if ($statements_analyzer) {
                $found_type = ConstFetchAnalyzer::getConstType(
                    $statements_analyzer,
                    $c->name,
                    $c->is_fully_qualified,
                    null
                );

                if ($found_type) {
                    return $found_type->getSingleAtomic();
                }
            }
        }

        return new TMixed;
    }

    /**
     * @param  string|int|float|bool|null $value
     */
    private static function getLiteralTypeFromScalarValue($value): Atomic
    {
        if (is_string($value)) {
            return new TLiteralString($value);
        }

        if (is_int($value)) {
            return new TLiteralInt($value);
        }

        if (is_float($value)) {
            return new TLiteralFloat($value);
        }

        if ($value === false) {
            return new TFalse;
        }

        if ($value === true) {
            return new TTrue;
        }

        return new TNull;
    }
}
