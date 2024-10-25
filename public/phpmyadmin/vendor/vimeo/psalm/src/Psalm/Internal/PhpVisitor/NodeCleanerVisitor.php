<?php

namespace Psalm\Internal\PhpVisitor;

use PhpParser;
use Psalm\Internal\Provider\NodeDataProvider;

/**
 * @internal
 */
class NodeCleanerVisitor extends PhpParser\NodeVisitorAbstract
{
    private $type_provider;

    public function __construct(NodeDataProvider $type_provider)
    {
        $this->type_provider = $type_provider;
    }

    public function enterNode(PhpParser\Node $node): ?int
    {
        if ($node instanceof PhpParser\Node\Expr) {
            $this->type_provider->clearNodeOfTypeAndAssertions($node);
        }

        return null;
    }
}
