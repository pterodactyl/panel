<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\MethodCallReturnTypeFetcher;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Issue\InvalidCast;
use Psalm\Issue\PossiblyInvalidCast;
use Psalm\Issue\RedundantCast;
use Psalm\Issue\RedundantCastGivenDocblockType;
use Psalm\Issue\RiskyCast;
use Psalm\Issue\UnrecognizedExpression;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClosedResource;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonspecificLiteralInt;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;

use function array_merge;
use function array_pop;
use function array_values;
use function get_class;
use function strtolower;

class CastAnalyzer
{
    /** @var string[] */
    private const PSEUDO_CASTABLE_CLASSES = [
        'SimpleXMLElement',
        'DOMNode',
        'GMP',
        'Decimal\Decimal',
    ];

    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Cast $stmt,
        Context $context
    ): bool {
        if ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $maybe_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($maybe_type) {
                if ($maybe_type->isInt()) {
                    if (!$maybe_type->from_calculation) {
                        self::handleRedundantCast($maybe_type, $statements_analyzer, $stmt);
                    }
                }

                $type = self::castIntAttempt(
                    $statements_analyzer,
                    $maybe_type,
                    $stmt->expr,
                    true
                );
            } else {
                $type = Type::getInt();
            }

            $statements_analyzer->node_data->setType($stmt, $type);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $maybe_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($maybe_type) {
                if ($maybe_type->isFloat()) {
                    self::handleRedundantCast($maybe_type, $statements_analyzer, $stmt);
                }

                $type = self::castFloatAttempt(
                    $statements_analyzer,
                    $maybe_type,
                    $stmt->expr,
                    true
                );
            } else {
                $type = Type::getFloat();
            }

            $statements_analyzer->node_data->setType($stmt, $type);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $maybe_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($maybe_type) {
                if ($maybe_type->isBool()) {
                    self::handleRedundantCast($maybe_type, $statements_analyzer, $stmt);
                }
            }

            $type = Type::getBool();

            if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph
            ) {
                $type->parent_nodes = $maybe_type->parent_nodes ?? [];
            }

            $statements_analyzer->node_data->setType($stmt, $type);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($stmt_expr_type) {
                if ($stmt_expr_type->isString()) {
                    self::handleRedundantCast($stmt_expr_type, $statements_analyzer, $stmt);
                }

                $stmt_type = self::castStringAttempt(
                    $statements_analyzer,
                    $context,
                    $stmt_expr_type,
                    $stmt->expr,
                    true
                );
            } else {
                $stmt_type = Type::getString();
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            if (!self::checkExprGeneralUse($statements_analyzer, $stmt, $context)) {
                return false;
            }

            $permissible_atomic_types = [];
            $all_permissible = false;

            if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
                if ($stmt_expr_type->isObjectType()) {
                    self::handleRedundantCast($stmt_expr_type, $statements_analyzer, $stmt);
                }

                $all_permissible = true;

                foreach ($stmt_expr_type->getAtomicTypes() as $type) {
                    if ($type instanceof Scalar) {
                        $objWithProps = new TObjectWithProperties(['scalar' => new Union([$type])]);
                        $permissible_atomic_types[] = $objWithProps;
                    } elseif ($type instanceof TKeyedArray) {
                        $permissible_atomic_types[] = new TObjectWithProperties($type->properties);
                    } else {
                        $all_permissible = false;
                        break;
                    }
                }
            }

            if ($permissible_atomic_types && $all_permissible) {
                $type = TypeCombiner::combine($permissible_atomic_types);
            } else {
                $type = Type::getObject();
            }

            if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph
            ) {
                $type->parent_nodes = $stmt_expr_type->parent_nodes ?? [];
            }

            $statements_analyzer->node_data->setType($stmt, $type);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            if (!self::checkExprGeneralUse($statements_analyzer, $stmt, $context)) {
                return false;
            }

            $permissible_atomic_types = [];
            $all_permissible = false;

            if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
                if ($stmt_expr_type->isArray()) {
                    self::handleRedundantCast($stmt_expr_type, $statements_analyzer, $stmt);
                }

                $all_permissible = true;

                foreach ($stmt_expr_type->getAtomicTypes() as $type) {
                    if ($type instanceof Scalar) {
                        $keyed_array = new TKeyedArray([new Union([$type])]);
                        $keyed_array->is_list = true;
                        $keyed_array->sealed = true;
                        $permissible_atomic_types[] = $keyed_array;
                    } elseif ($type instanceof TNull) {
                        $permissible_atomic_types[] = new TArray([Type::getEmpty(), Type::getEmpty()]);
                    } elseif ($type instanceof TArray
                        || $type instanceof TList
                        || $type instanceof TKeyedArray
                    ) {
                        $permissible_atomic_types[] = clone $type;
                    } else {
                        $all_permissible = false;
                        break;
                    }
                }
            }

            if ($permissible_atomic_types && $all_permissible) {
                $type = TypeCombiner::combine($permissible_atomic_types);
            } else {
                $type = Type::getArray();
            }

            if ($statements_analyzer->data_flow_graph) {
                $type->parent_nodes = $stmt_expr_type->parent_nodes ?? [];
            }

            $statements_analyzer->node_data->setType($stmt, $type);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Unset_
            && $statements_analyzer->getCodebase()->php_major_version < 8
        ) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $statements_analyzer->node_data->setType($stmt, Type::getNull());

            return true;
        }

        IssueBuffer::maybeAdd(
            new UnrecognizedExpression(
                'Psalm does not understand the cast ' . get_class($stmt),
                new CodeLocation($statements_analyzer->getSource(), $stmt)
            ),
            $statements_analyzer->getSuppressedIssues()
        );

        return false;
    }

    public static function castIntAttempt(
        StatementsAnalyzer $statements_analyzer,
        Union $stmt_type,
        PhpParser\Node\Expr $stmt,
        bool $explicit_cast = false
    ): Union {
        $codebase = $statements_analyzer->getCodebase();

        $risky_cast = [];
        $invalid_casts = [];
        $valid_ints = [];
        $castable_types = [];

        $atomic_types = $stmt_type->getAtomicTypes();

        $parent_nodes = [];

        if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph) {
            $parent_nodes = $stmt_type->parent_nodes;
        }

        while ($atomic_types) {
            $atomic_type = array_pop($atomic_types);

            if ($atomic_type instanceof TInt) {
                $valid_ints[] = $atomic_type;

                continue;
            }

            if ($atomic_type instanceof TFloat) {
                if ($atomic_type instanceof TLiteralFloat) {
                    $valid_ints[] = new TLiteralInt((int) $atomic_type->value);
                } else {
                    $castable_types[] = new TInt();
                }

                continue;
            }

            if ($atomic_type instanceof TString) {
                if ($atomic_type instanceof TLiteralString) {
                    $valid_ints[] = new TLiteralInt((int) $atomic_type->value);
                } elseif ($atomic_type instanceof TNumericString) {
                    $castable_types[] = new TInt();
                } else {
                    // any normal string is technically $valid_int[] = new TLiteralInt(0);
                    // however we cannot be certain that it's not inferred, therefore less strict
                    $castable_types[] = new TInt();
                }

                continue;
            }

            if ($atomic_type instanceof TNull || $atomic_type instanceof TFalse) {
                $valid_ints[] = new TLiteralInt(0);
                continue;
            }

            if ($atomic_type instanceof TTrue) {
                $valid_ints[] = new TLiteralInt(1);
                continue;
            }

            if ($atomic_type instanceof TBool) {
                // do NOT use TIntRange here, as it will cause invalid behavior, e.g. bitwiseAssignment
                $valid_ints[] = new TLiteralInt(0);
                $valid_ints[] = new TLiteralInt(1);
                continue;
            }

            // could be invalid, but allow it, as it is allowed for TString below too
            if ($atomic_type instanceof TMixed
                || $atomic_type instanceof TClosedResource
                || $atomic_type instanceof TResource
                || $atomic_type instanceof Scalar
            ) {
                $castable_types[] = new TInt();

                continue;
            }

            if ($atomic_type instanceof TNamedObject) {
                $intersection_types = [$atomic_type];

                if ($atomic_type->extra_types) {
                    $intersection_types = array_merge($intersection_types, $atomic_type->extra_types);
                }

                foreach ($intersection_types as $intersection_type) {
                    if (!$intersection_type instanceof TNamedObject) {
                        continue;
                    }

                    // prevent "Could not get class storage for mixed"
                    if (!$codebase->classExists($intersection_type->value)) {
                        continue;
                    }

                    foreach (self::PSEUDO_CASTABLE_CLASSES as $pseudo_castable_class) {
                        if (strtolower($intersection_type->value) === strtolower($pseudo_castable_class)
                            || $codebase->classExtends(
                                $intersection_type->value,
                                $pseudo_castable_class
                            )
                        ) {
                            $castable_types[] = new TInt();
                            continue 3;
                        }
                    }
                }
            }

            if ($atomic_type instanceof TNonEmptyArray
                || $atomic_type instanceof TNonEmptyList
            ) {
                $risky_cast[] = $atomic_type->getId();

                $valid_ints[] = new TLiteralInt(1);

                continue;
            }

            if ($atomic_type instanceof TArray
                || $atomic_type instanceof TList
                || $atomic_type instanceof TKeyedArray
            ) {
                // if type is not specific, it can be both 0 or 1, depending on whether the array has data or not
                // welcome to off-by-one hell if that happens :-)
                $risky_cast[] = $atomic_type->getId();

                $valid_ints[] = new TLiteralInt(0);
                $valid_ints[] = new TLiteralInt(1);

                continue;
            }

            if ($atomic_type instanceof TTemplateParam) {
                $atomic_types = array_merge($atomic_types, $atomic_type->as->getAtomicTypes());

                continue;
            }

            // always 1 for "error" cases
            $valid_ints[] = new TLiteralInt(1);

            $invalid_casts[] = $atomic_type->getId();
        }

        if ($invalid_casts) {
            IssueBuffer::maybeAdd(
                new InvalidCast(
                    $invalid_casts[0] . ' cannot be cast to int',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            );
        } elseif ($risky_cast) {
            IssueBuffer::maybeAdd(
                new RiskyCast(
                    'Casting ' . $risky_cast[0] . ' to int has possibly unintended value of 0/1',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            );
        } elseif ($explicit_cast && !$castable_types) {
            // todo: emit error here
        }

        $valid_types = array_merge($valid_ints, $castable_types);

        if (!$valid_types) {
            $int_type = Type::getInt();
        } else {
            $int_type = TypeCombiner::combine(
                $valid_types,
                $codebase
            );
        }

        if ($statements_analyzer->data_flow_graph) {
            $int_type->parent_nodes = $parent_nodes;
        }

        return $int_type;
    }

    public static function castFloatAttempt(
        StatementsAnalyzer $statements_analyzer,
        Union $stmt_type,
        PhpParser\Node\Expr $stmt,
        bool $explicit_cast = false
    ): Union {
        $codebase = $statements_analyzer->getCodebase();

        $risky_cast = [];
        $invalid_casts = [];
        $valid_floats = [];
        $castable_types = [];

        $atomic_types = $stmt_type->getAtomicTypes();

        $parent_nodes = [];

        if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph) {
            $parent_nodes = $stmt_type->parent_nodes;
        }

        while ($atomic_types) {
            $atomic_type = array_pop($atomic_types);

            if ($atomic_type instanceof TFloat) {
                $valid_floats[] = $atomic_type;

                continue;
            }

            if ($atomic_type instanceof TInt) {
                if ($atomic_type instanceof TLiteralInt) {
                    $valid_floats[] = new TLiteralFloat((float) $atomic_type->value);
                } else {
                    $castable_types[] = new TFloat();
                }

                continue;
            }

            if ($atomic_type instanceof TString) {
                if ($atomic_type instanceof TLiteralString) {
                    $valid_floats[] = new TLiteralFloat((float) $atomic_type->value);
                } elseif ($atomic_type instanceof TNumericString) {
                    $castable_types[] = new TFloat();
                } else {
                    // any normal string is technically $valid_floats[] = new TLiteralFloat(0.0);
                    // however we cannot be certain that it's not inferred, therefore less strict
                    $castable_types[] = new TFloat();
                }

                continue;
            }

            if ($atomic_type instanceof TNull || $atomic_type instanceof TFalse) {
                $valid_floats[] = new TLiteralFloat(0.0);
                continue;
            }

            if ($atomic_type instanceof TTrue) {
                $valid_floats[] = new TLiteralFloat(1.0);
                continue;
            }

            if ($atomic_type instanceof TBool) {
                $valid_floats[] = new TLiteralFloat(0.0);
                $valid_floats[] = new TLiteralFloat(1.0);
                continue;
            }

            // could be invalid, but allow it, as it is allowed for TString below too
            if ($atomic_type instanceof TMixed
                || $atomic_type instanceof TClosedResource
                || $atomic_type instanceof TResource
                || $atomic_type instanceof Scalar
            ) {
                $castable_types[] = new TFloat();

                continue;
            }

            if ($atomic_type instanceof TNamedObject) {
                $intersection_types = [$atomic_type];

                if ($atomic_type->extra_types) {
                    $intersection_types = array_merge($intersection_types, $atomic_type->extra_types);
                }

                foreach ($intersection_types as $intersection_type) {
                    if (!$intersection_type instanceof TNamedObject) {
                        continue;
                    }

                    // prevent "Could not get class storage for mixed"
                    if (!$codebase->classExists($intersection_type->value)) {
                        continue;
                    }

                    foreach (self::PSEUDO_CASTABLE_CLASSES as $pseudo_castable_class) {
                        if (strtolower($intersection_type->value) === strtolower($pseudo_castable_class)
                            || $codebase->classExtends(
                                $intersection_type->value,
                                $pseudo_castable_class
                            )
                        ) {
                            $castable_types[] = new TFloat();
                            continue 3;
                        }
                    }
                }
            }

            if ($atomic_type instanceof TNonEmptyArray
                || $atomic_type instanceof TNonEmptyList
            ) {
                $risky_cast[] = $atomic_type->getId();

                $valid_floats[] = new TLiteralFloat(1.0);

                continue;
            }

            if ($atomic_type instanceof TArray
                || $atomic_type instanceof TList
                || $atomic_type instanceof TKeyedArray
            ) {
                // if type is not specific, it can be both 0 or 1, depending on whether the array has data or not
                // welcome to off-by-one hell if that happens :-)
                $risky_cast[] = $atomic_type->getId();

                $valid_floats[] = new TLiteralFloat(0.0);
                $valid_floats[] = new TLiteralFloat(1.0);

                continue;
            }

            if ($atomic_type instanceof TTemplateParam) {
                $atomic_types = array_merge($atomic_types, $atomic_type->as->getAtomicTypes());

                continue;
            }

            // always 1.0 for "error" cases
            $valid_floats[] = new TLiteralFloat(1.0);

            $invalid_casts[] = $atomic_type->getId();
        }

        if ($invalid_casts) {
            IssueBuffer::maybeAdd(
                new InvalidCast(
                    $invalid_casts[0] . ' cannot be cast to float',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            );
        } elseif ($risky_cast) {
            IssueBuffer::maybeAdd(
                new RiskyCast(
                    'Casting ' . $risky_cast[0] . ' to float has possibly unintended value of 0.0/1.0',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            );
        } elseif ($explicit_cast && !$castable_types) {
            // todo: emit error here
        }

        $valid_types = array_merge($valid_floats, $castable_types);

        if (!$valid_types) {
            $float_type = Type::getFloat();
        } else {
            $float_type = TypeCombiner::combine(
                $valid_types,
                $codebase
            );
        }

        if ($statements_analyzer->data_flow_graph) {
            $float_type->parent_nodes = $parent_nodes;
        }

        return $float_type;
    }

    public static function castStringAttempt(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        Union $stmt_type,
        PhpParser\Node\Expr $stmt,
        bool $explicit_cast = false
    ): Union {
        $codebase = $statements_analyzer->getCodebase();

        $invalid_casts = [];
        $valid_strings = [];
        $castable_types = [];

        $atomic_types = $stmt_type->getAtomicTypes();

        $parent_nodes = [];

        if ($statements_analyzer->data_flow_graph) {
            $parent_nodes = $stmt_type->parent_nodes;
        }

        while ($atomic_types) {
            $atomic_type = array_pop($atomic_types);

            if ($atomic_type instanceof TFloat
                || $atomic_type instanceof TInt
                || $atomic_type instanceof TNumeric
            ) {
                if ($atomic_type instanceof TLiteralInt || $atomic_type instanceof TLiteralFloat) {
                    $castable_types[] = new TLiteralString((string) $atomic_type->value);
                } elseif ($atomic_type instanceof TNonspecificLiteralInt) {
                    $castable_types[] = new TNonspecificLiteralString();
                } else {
                    $castable_types[] = new TNumericString();
                }

                continue;
            }

            if ($atomic_type instanceof TString) {
                $valid_strings[] = $atomic_type;

                continue;
            }

            if ($atomic_type instanceof TNull
                || $atomic_type instanceof TFalse
            ) {
                $valid_strings[] = new TLiteralString('');
                continue;
            }

            if ($atomic_type instanceof TTrue
            ) {
                $valid_strings[] = new TLiteralString('1');
                continue;
            }

            if ($atomic_type instanceof TBool
            ) {
                $valid_strings[] = new TLiteralString('1');
                $valid_strings[] = new TLiteralString('');
                continue;
            }

            if ($atomic_type instanceof TClosedResource
               || $atomic_type instanceof TResource
            ) {
                $castable_types[] = new TNonEmptyString();

                continue;
            }

            if ($atomic_type instanceof TMixed
                || $atomic_type instanceof Scalar
            ) {
                $castable_types[] = new TString();

                continue;
            }

            if ($atomic_type instanceof TNamedObject
                || $atomic_type instanceof TObjectWithProperties
            ) {
                $intersection_types = [$atomic_type];

                if ($atomic_type->extra_types) {
                    $intersection_types = array_merge($intersection_types, $atomic_type->extra_types);
                }

                foreach ($intersection_types as $intersection_type) {
                    if ($intersection_type instanceof TNamedObject) {
                        $intersection_method_id = new MethodIdentifier(
                            $intersection_type->value,
                            '__tostring'
                        );

                        if ($codebase->methods->methodExists(
                            $intersection_method_id,
                            $context->calling_method_id,
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        )) {
                            $return_type = $codebase->methods->getMethodReturnType(
                                $intersection_method_id,
                                $self_class
                            ) ?? Type::getString();

                            $declaring_method_id = $codebase->methods->getDeclaringMethodId($intersection_method_id);

                            MethodCallReturnTypeFetcher::taintMethodCallResult(
                                $statements_analyzer,
                                $return_type,
                                $stmt,
                                $stmt,
                                [],
                                $intersection_method_id,
                                $declaring_method_id,
                                $intersection_type->value . '::__toString',
                                $context
                            );

                            if ($statements_analyzer->data_flow_graph) {
                                $parent_nodes = array_merge($return_type->parent_nodes, $parent_nodes);
                            }

                            $castable_types = array_merge(
                                $castable_types,
                                array_values($return_type->getAtomicTypes())
                            );

                            continue 2;
                        }
                    }

                    if ($intersection_type instanceof TObjectWithProperties
                        && isset($intersection_type->methods['__toString'])
                    ) {
                        $castable_types[] = new TString();

                        continue 2;
                    }
                }
            }

            if ($atomic_type instanceof TTemplateParam) {
                $atomic_types = array_merge($atomic_types, $atomic_type->as->getAtomicTypes());

                continue;
            }

            $invalid_casts[] = $atomic_type->getId();
        }

        if ($invalid_casts) {
            if ($valid_strings || $castable_types) {
                IssueBuffer::maybeAdd(
                    new PossiblyInvalidCast(
                        $invalid_casts[0] . ' cannot be cast to string',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            } else {
                IssueBuffer::maybeAdd(
                    new InvalidCast(
                        $invalid_casts[0] . ' cannot be cast to string',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }
        } elseif ($explicit_cast && !$castable_types) {
            // todo: emit error here
        }

        $valid_types = array_merge($valid_strings, $castable_types);

        if (!$valid_types) {
            $str_type = Type::getString();
        } else {
            $str_type = TypeCombiner::combine(
                $valid_types,
                $codebase
            );
        }

        if ($statements_analyzer->data_flow_graph) {
            $str_type->parent_nodes = $parent_nodes;
        }

        return $str_type;
    }

    private static function checkExprGeneralUse(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Cast $stmt,
        Context $context
    ): bool {
        $was_inside_general_use = $context->inside_general_use;
        $context->inside_general_use = true;
        $retVal = ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context);
        $context->inside_general_use = $was_inside_general_use;
        return $retVal;
    }

    private static function handleRedundantCast(
        Union $maybe_type,
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Cast $stmt
    ): void {
        $codebase = $statements_analyzer->getCodebase();
        $project_analyzer = $statements_analyzer->getProjectAnalyzer();

        $file_manipulation = null;
        if ($maybe_type->from_docblock) {
            $issue = new RedundantCastGivenDocblockType(
                'Redundant cast to ' . $maybe_type->getKey() . ' given docblock-provided type',
                new CodeLocation($statements_analyzer->getSource(), $stmt)
            );

            if ($codebase->alter_code
                && isset($project_analyzer->getIssuesToFix()['RedundantCastGivenDocblockType'])
            ) {
                $file_manipulation = new FileManipulation(
                    (int) $stmt->getAttribute('startFilePos'),
                    (int) $stmt->expr->getAttribute('startFilePos'),
                    ''
                );
            }
        } else {
            $issue = new RedundantCast(
                'Redundant cast to ' . $maybe_type->getKey(),
                new CodeLocation($statements_analyzer->getSource(), $stmt)
            );

            if ($codebase->alter_code
                && isset($project_analyzer->getIssuesToFix()['RedundantCast'])
            ) {
                $file_manipulation = new FileManipulation(
                    (int) $stmt->getAttribute('startFilePos'),
                    (int) $stmt->expr->getAttribute('startFilePos'),
                    ''
                );
            }
        }

        if ($file_manipulation) {
            FileManipulationBuffer::add($statements_analyzer->getFilePath(), [$file_manipulation]);
        }


        if (IssueBuffer::accepts($issue, $statements_analyzer->getSuppressedIssues())) {
            // fall through
        }
    }
}
