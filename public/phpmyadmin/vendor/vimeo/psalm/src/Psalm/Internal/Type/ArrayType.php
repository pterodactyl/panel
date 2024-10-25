<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Union;

/**
 * @internal
 */
class ArrayType
{
    /** @var Union */
    public $key;

    /** @var Union */
    public $value;

    /** @var bool */
    public $is_list;

    public function __construct(Union $key, Union $value, bool $is_list)
    {
        $this->key = $key;
        $this->value = $value;
        $this->is_list = $is_list;
    }

    public static function infer(Atomic $type): ?self
    {
        if ($type instanceof TKeyedArray) {
            return new self(
                $type->getGenericKeyType(),
                $type->getGenericValueType(),
                $type->is_list
            );
        }

        if ($type instanceof TList) {
            return new self(
                Type::getInt(),
                $type->type_param,
                true
            );
        }

        if ($type instanceof TArray) {
            return new self(
                $type->type_params[0],
                $type->type_params[1],
                false
            );
        }

        return null;
    }
}
