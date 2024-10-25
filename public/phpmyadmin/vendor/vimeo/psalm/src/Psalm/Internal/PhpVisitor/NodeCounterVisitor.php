<?php

namespace Psalm\Internal\PhpVisitor;

use PhpParser;

/**
 * @internal
 */
class NodeCounterVisitor extends PhpParser\NodeVisitorAbstract
{
    /** @var int */
    public $count = 0;

    public function enterNode(PhpParser\Node $node): ?int
    {
        $this->count++;

        return null;
    }
}
