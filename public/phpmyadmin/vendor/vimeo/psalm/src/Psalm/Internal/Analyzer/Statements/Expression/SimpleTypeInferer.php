<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use InvalidArgumentException;
use PhpParser;
use Psalm\Aliases;
use Psalm\Codebase;
use Psalm\Exception\CircularReferenceException;
use Psalm\FileSource;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOp\ArithmeticOpAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\StatementsSource;
use Psalm\Storage\ClassConstantStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;
use ReflectionProperty;

use function array_merge;
use function array_values;
use function count;
use function is_string;
use function preg_match;
use function strtolower;

use const PHP_INT_MAX;

/**
 * This class takes a statement and return its type by analyzing each part of the statement if necessary
 */
class SimpleTypeInferer
{
    /**
     * @param   ?array<string, ClassConstantStorage> $existing_class_constants
     */
    public static function infer(
        Codebase $codebase,
        NodeDataProvider $nodes,
        PhpParser\Node\Expr $stmt,
        Aliases $aliases,
        FileSource $file_source = null,
        ?array $existing_class_constants = null,
        ?string $fq_classlike_name = null
    ): ?Union {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
                $left = self::infer(
                    $codebase,
                    $nodes,
                    $stmt->left,
                    $aliases,
                    $file_source,
                    $existing_class_constants,
                    $fq_classlike_name
                );
                $right = self::infer(
                    $codebase,
                    $nodes,
                    $stmt->right,
                    $aliases,
                    $file_source,
                    $existing_class_constants,
                    $fq_classlike_name
                );

                if ($left
                    && $right
                ) {
                    if ($left->isSingleStringLiteral()
                        && $right->isSingleStringLiteral()
                    ) {
                        $result = $left->getSingleStringLiteral()->value . $right->getSingleStringLiteral()->value;

                        return Type::getString($result);
                    }

                    if ($left->isSingle() && $left->getSingleAtomic() instanceof TNonEmptyString) {
                        return new Union([new TNonEmptyString()]);
                    }

                    if ($right->isSingle() && $right->getSingleAtomic() instanceof TNonEmptyString) {
                        return new Union([new TNonEmptyString()]);
                    }
                }

                return Type::getString();
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Greater
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Smaller
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
            ) {
                return Type::getBool();
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Coalesce) {
                return null;
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Spaceship) {
                return new Union(
                    [
                        new TLiteralInt(-1),
                        new TLiteralInt(0),
                        new TLiteralInt(1)
                    ]
                );
            }

            $stmt_left_type = self::infer(
                $codebase,
                $nodes,
                $stmt->left,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );

            $stmt_right_type = self::infer(
                $codebase,
                $nodes,
                $stmt->right,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );

            if (!$stmt_left_type || !$stmt_right_type) {
                return null;
            }

            $nodes->setType(
                $stmt->left,
                $stmt_left_type
            );

            $nodes->setType(
                $stmt->right,
                $stmt_right_type
            );

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Plus
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Minus
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mod
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mul
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Pow
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\ShiftRight
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\ShiftLeft
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd
            ) {
                ArithmeticOpAnalyzer::analyze(
                    $file_source instanceof StatementsSource ? $file_source : null,
                    $nodes,
                    $stmt->left,
                    $stmt->right,
                    $stmt,
                    $result_type
                );

                if ($result_type) {
                    return $result_type;
                }

                return null;
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Div
                && ($stmt_left_type->hasInt() || $stmt_left_type->hasFloat())
                && ($stmt_right_type->hasInt() || $stmt_right_type->hasFloat())
            ) {
                return Type::combineUnionTypes(Type::getFloat(), Type::getInt());
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            $name = strtolower($stmt->name->parts[0]);
            if ($name === 'false') {
                return Type::getFalse();
            }

            if ($name === 'true') {
                return Type::getTrue();
            }

            if ($name === 'null') {
                return Type::getNull();
            }

            if ($stmt->name->parts[0] === '__NAMESPACE__') {
                return Type::getString($aliases->namespace);
            }

            return null;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Dir
            || $stmt instanceof PhpParser\Node\Scalar\MagicConst\File
        ) {
            return new Union([new TNonEmptyString()]);
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Line) {
            return Type::getInt();
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Class_
            || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Method
            || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Trait_
            || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Function_
        ) {
            return Type::getString();
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Namespace_) {
            return Type::getString($aliases->namespace);
        }

        if ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            if ($stmt->class instanceof PhpParser\Node\Name
                && $stmt->name instanceof PhpParser\Node\Identifier
                && $fq_classlike_name
                && $stmt->class->parts !== ['static']
                && $stmt->class->parts !== ['parent']
            ) {
                if (isset($existing_class_constants[$stmt->name->name])
                    && $existing_class_constants[$stmt->name->name]->type
                ) {
                    if ($stmt->class->parts === ['self']) {
                        return clone $existing_class_constants[$stmt->name->name]->type;
                    }
                }

                if ($stmt->class->parts === ['self']) {
                    $const_fq_class_name = $fq_classlike_name;
                } else {
                    $const_fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                        $stmt->class,
                        $aliases
                    );
                }

                if (strtolower($const_fq_class_name) === strtolower($fq_classlike_name)
                    && isset($existing_class_constants[$stmt->name->name])
                    && $existing_class_constants[$stmt->name->name]->type
                ) {
                    return clone $existing_class_constants[$stmt->name->name]->type;
                }

                if (strtolower($stmt->name->name) === 'class') {
                    return Type::getLiteralClassString($const_fq_class_name, true);
                }

                if ($existing_class_constants === null
                    && $file_source instanceof StatementsAnalyzer
                ) {
                    try {
                        $foreign_class_constant = $codebase->classlikes->getClassConstantType(
                            $const_fq_class_name,
                            $stmt->name->name,
                            ReflectionProperty::IS_PRIVATE,
                            $file_source
                        );

                        if ($foreign_class_constant) {
                            return clone $foreign_class_constant;
                        }

                        return null;
                    } catch (InvalidArgumentException | CircularReferenceException $e) {
                        return null;
                    }
                }
            }

            if ($stmt->name instanceof PhpParser\Node\Identifier && strtolower($stmt->name->name) === 'class') {
                return Type::getClassString();
            }

            return null;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\String_) {
            return Type::getString($stmt->value);
        }

        if ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            return Type::getInt(false, $stmt->value);
        }

        if ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            return Type::getFloat($stmt->value);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Array_) {
            return self::inferArrayType(
                $codebase,
                $nodes,
                $stmt,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            return Type::getInt();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            return Type::getFloat();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            return Type::getBool();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            return Type::getString();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            return Type::getObject();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            return Type::getArray();
        }

        if ($stmt instanceof PhpParser\Node\Expr\UnaryMinus || $stmt instanceof PhpParser\Node\Expr\UnaryPlus) {
            $type_to_invert = self::infer(
                $codebase,
                $nodes,
                $stmt->expr,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );

            if (!$type_to_invert) {
                return null;
            }

            foreach ($type_to_invert->getAtomicTypes() as $type_part) {
                if ($type_part instanceof TLiteralInt
                    && $stmt instanceof PhpParser\Node\Expr\UnaryMinus
                ) {
                    $type_part->value = -$type_part->value;
                } elseif ($type_part instanceof TLiteralFloat
                    && $stmt instanceof PhpParser\Node\Expr\UnaryMinus
                ) {
                    $type_part->value = -$type_part->value;
                }
            }

            return $type_to_invert;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            if ($stmt->var instanceof PhpParser\Node\Expr\ClassConstFetch
                && $stmt->dim
            ) {
                $array_type = self::infer(
                    $codebase,
                    $nodes,
                    $stmt->var,
                    $aliases,
                    $file_source,
                    $existing_class_constants,
                    $fq_classlike_name
                );

                $dim_type = self::infer(
                    $codebase,
                    $nodes,
                    $stmt->dim,
                    $aliases,
                    $file_source,
                    $existing_class_constants,
                    $fq_classlike_name
                );

                if ($array_type !== null && $dim_type !== null) {
                    if ($dim_type->isSingleStringLiteral()) {
                        $dim_value = $dim_type->getSingleStringLiteral()->value;
                    } elseif ($dim_type->isSingleIntLiteral()) {
                        $dim_value = $dim_type->getSingleIntLiteral()->value;
                    } else {
                        return null;
                    }

                    foreach ($array_type->getAtomicTypes() as $array_atomic_type) {
                        if ($array_atomic_type instanceof TKeyedArray) {
                            if (isset($array_atomic_type->properties[$dim_value])) {
                                return clone $array_atomic_type->properties[$dim_value];
                            }

                            return null;
                        }
                    }
                }
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\New_) {
            $resolved_class_name = $stmt->class->getAttribute('resolvedName');

            if (!is_string($resolved_class_name)) {
                return null;
            }

            return new Union([
                new Type\Atomic\TNamedObject($resolved_class_name)
            ]);
        }

        return null;
    }

    /**
     * @param   ?array<string, ClassConstantStorage> $existing_class_constants
     */
    private static function inferArrayType(
        Codebase $codebase,
        NodeDataProvider $nodes,
        PhpParser\Node\Expr\Array_ $stmt,
        Aliases $aliases,
        FileSource $file_source = null,
        ?array $existing_class_constants = null,
        ?string $fq_classlike_name = null
    ): ?Union {
        if (count($stmt->items) === 0) {
            return Type::getEmptyArray();
        }

        $array_creation_info = new ArrayCreationInfo();

        foreach ($stmt->items as $item) {
            if ($item === null) {
                continue;
            }

            if (!self::handleArrayItem(
                $codebase,
                $nodes,
                $array_creation_info,
                $item,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            )) {
                return null;
            }
        }

        $item_key_type = null;
        if ($array_creation_info->item_key_atomic_types) {
            $item_key_type = TypeCombiner::combine(
                $array_creation_info->item_key_atomic_types,
                null,
                false,
                true,
                30
            );
        }

        $item_value_type = null;
        if ($array_creation_info->item_value_atomic_types) {
            $item_value_type = TypeCombiner::combine(
                $array_creation_info->item_value_atomic_types,
                null,
                false,
                true,
                30
            );
        }

        // if this array looks like an object-like array, let's return that instead
        if ($item_value_type
            && $item_key_type
            && ($item_key_type->hasString() || $item_key_type->hasInt())
            && $array_creation_info->can_create_objectlike
            && $array_creation_info->property_types
        ) {
            $objectlike = new TKeyedArray(
                $array_creation_info->property_types,
                $array_creation_info->class_strings
            );
            $objectlike->sealed = true;
            $objectlike->is_list = $array_creation_info->all_list;
            return new Union([$objectlike]);
        }

        if (!$item_key_type || !$item_value_type) {
            return null;
        }

        if ($array_creation_info->all_list) {
            return new Union([
                new TNonEmptyList($item_value_type),
            ]);
        }

        return new Union([
            new TNonEmptyArray([
                $item_key_type,
                $item_value_type,
            ]),
        ]);
    }

    /**
     * @param   ?array<string, ClassConstantStorage> $existing_class_constants
     */
    private static function handleArrayItem(
        Codebase $codebase,
        NodeDataProvider $nodes,
        ArrayCreationInfo $array_creation_info,
        PhpParser\Node\Expr\ArrayItem $item,
        Aliases $aliases,
        FileSource $file_source = null,
        ?array $existing_class_constants = null,
        ?string $fq_classlike_name = null
    ): bool {
        if ($item->unpack) {
            $unpacked_array_type = self::infer(
                $codebase,
                $nodes,
                $item->value,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );

            if (!$unpacked_array_type) {
                return false;
            }

            return self::handleUnpackedArray($array_creation_info, $unpacked_array_type);
        }

        $single_item_key_type = null;
        $item_is_list_item = false;
        $item_key_value = null;

        if ($item->key) {
            $single_item_key_type = self::infer(
                $codebase,
                $nodes,
                $item->key,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );

            if ($single_item_key_type) {
                $key_type = $single_item_key_type;
                if ($key_type->isNull()) {
                    $key_type = Type::getString('');
                }
                if ($item->key instanceof PhpParser\Node\Scalar\String_
                    && preg_match('/^(0|[1-9][0-9]*)$/', $item->key->value)
                    && (
                        (int) $item->key->value < PHP_INT_MAX ||
                        $item->key->value === (string) PHP_INT_MAX
                    )
                ) {
                    $key_type = Type::getInt(false, (int) $item->key->value);
                }

                $array_creation_info->item_key_atomic_types = array_merge(
                    $array_creation_info->item_key_atomic_types,
                    array_values($key_type->getAtomicTypes())
                );

                if ($key_type->isSingleStringLiteral()) {
                    $item_key_literal_type = $key_type->getSingleStringLiteral();
                    $item_key_value = $item_key_literal_type->value;

                    if ($item_key_literal_type instanceof TLiteralClassString) {
                        $array_creation_info->class_strings[$item_key_value] = true;
                    }
                } elseif ($key_type->isSingleIntLiteral()) {
                    $item_key_value = $key_type->getSingleIntLiteral()->value;

                    if ($item_key_value >= $array_creation_info->int_offset) {
                        if ($item_key_value === $array_creation_info->int_offset) {
                            $item_is_list_item = true;
                        }
                        $array_creation_info->int_offset = $item_key_value + 1;
                    }
                }
            }
        } else {
            $item_is_list_item = true;
            $item_key_value = $array_creation_info->int_offset++;
            $array_creation_info->item_key_atomic_types[] = new TLiteralInt($item_key_value);
        }

        $single_item_value_type = self::infer(
            $codebase,
            $nodes,
            $item->value,
            $aliases,
            $file_source,
            $existing_class_constants,
            $fq_classlike_name
        );

        if (!$single_item_value_type) {
            return false;
        }

        $config = $codebase->config;

        $array_creation_info->all_list = $array_creation_info->all_list && $item_is_list_item;

        if ($item->key instanceof PhpParser\Node\Scalar\String_
            || $item->key instanceof PhpParser\Node\Scalar\LNumber
            || !$item->key
        ) {
            if ($item_key_value !== null
                && count($array_creation_info->property_types) <= $config->max_shaped_array_size
            ) {
                $array_creation_info->property_types[$item_key_value] = $single_item_value_type;
            } else {
                $array_creation_info->can_create_objectlike = false;
            }
        } else {
            $dim_type = $single_item_key_type;

            if (!$dim_type) {
                return false;
            }

            if (count($dim_type->getAtomicTypes()) > 1
                || $dim_type->hasMixed()
                || count($array_creation_info->property_types) > $config->max_shaped_array_size
            ) {
                $array_creation_info->can_create_objectlike = false;
            } else {
                $atomic_type = $dim_type->getSingleAtomic();

                if ($atomic_type instanceof TLiteralInt
                    || $atomic_type instanceof TLiteralString
                ) {
                    if ($atomic_type instanceof TLiteralClassString) {
                        $array_creation_info->class_strings[$atomic_type->value] = true;
                    }

                    $array_creation_info->property_types[$atomic_type->value] = $single_item_value_type;
                } else {
                    $array_creation_info->can_create_objectlike = false;
                }
            }
        }

        $array_creation_info->item_value_atomic_types = array_merge(
            $array_creation_info->item_value_atomic_types,
            array_values($single_item_value_type->getAtomicTypes())
        );

        return true;
    }

    private static function handleUnpackedArray(
        ArrayCreationInfo $array_creation_info,
        Union $unpacked_array_type
    ): bool {
        foreach ($unpacked_array_type->getAtomicTypes() as $unpacked_atomic_type) {
            if ($unpacked_atomic_type instanceof TKeyedArray) {
                foreach ($unpacked_atomic_type->properties as $key => $property_value) {
                    if (is_string($key)) {
                        $new_offset = $key;
                        $array_creation_info->item_key_atomic_types[] = new TLiteralString($new_offset);
                    } else {
                        $new_offset = $array_creation_info->int_offset++;
                        $array_creation_info->item_key_atomic_types[] = new TLiteralInt($new_offset);
                    }

                    $array_creation_info->item_value_atomic_types = array_merge(
                        $array_creation_info->item_value_atomic_types,
                        array_values($property_value->getAtomicTypes())
                    );

                    $array_creation_info->array_keys[$new_offset] = true;
                    $array_creation_info->property_types[$new_offset] = $property_value;
                }
            } elseif ($unpacked_atomic_type instanceof TArray) {
                if ($unpacked_atomic_type->type_params[1]->isEmpty()) {
                    continue;
                }
                $array_creation_info->can_create_objectlike = false;

                if ($unpacked_atomic_type->type_params[0]->hasString()) {
                    $array_creation_info->item_key_atomic_types[] = new TString();
                }

                if ($unpacked_atomic_type->type_params[0]->hasInt()) {
                    $array_creation_info->item_key_atomic_types[] = new TInt();
                }

                $array_creation_info->item_value_atomic_types = array_merge(
                    $array_creation_info->item_value_atomic_types,
                    array_values(
                        isset($unpacked_atomic_type->type_params[1])
                            ? $unpacked_atomic_type->type_params[1]->getAtomicTypes()
                            : [new TMixed()]
                    )
                );
            } elseif ($unpacked_atomic_type instanceof TList) {
                if ($unpacked_atomic_type->type_param->isEmpty()) {
                    continue;
                }
                $array_creation_info->can_create_objectlike = false;

                $array_creation_info->item_key_atomic_types[] = new TInt();

                $array_creation_info->item_value_atomic_types = array_merge(
                    $array_creation_info->item_value_atomic_types,
                    array_values($unpacked_atomic_type->type_param->getAtomicTypes())
                );
            }
        }
        return true;
    }
}
