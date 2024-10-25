<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class Value extends ParseTree
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var int
     */
    public $offset_start;

    /**
     * @var int
     */
    public $offset_end;

    /**
     * @var ?string
     */
    public $text;

    /**
     * @param ParseTree|null $parent
     */
    public function __construct(
        string $value,
        int $offset_start,
        int $offset_end,
        ?string $text,
        ParseTree $parent = null
    ) {
        $this->offset_start = $offset_start;
        $this->offset_end = $offset_end;
        $this->value = $value;
        $this->parent = $parent;
        $this->text = $text === $value ? null : $text;
    }
}
