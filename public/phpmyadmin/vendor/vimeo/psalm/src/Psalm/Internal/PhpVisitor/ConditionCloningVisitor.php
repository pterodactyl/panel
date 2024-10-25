<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeVisitorAbstract;
use Psalm\Internal\Provider\NodeDataProvider;

class ConditionCloningVisitor extends NodeVisitorAbstract
{
    private $type_provider;

    public function __construct(NodeDataProvider $old_type_provider)
    {
        $this->type_provider = $old_type_provider;
    }

    /**
     * @return Node\Expr
     */
    public function enterNode(Node $node): Node
    {
        /** @var Expr $node */
        $origNode = $node;

        $node = clone $node;

        $node_type = $this->type_provider->getType($origNode);

        if ($node_type) {
            $this->type_provider->setType($node, clone $node_type);
        }

        return $node;
    }
}
