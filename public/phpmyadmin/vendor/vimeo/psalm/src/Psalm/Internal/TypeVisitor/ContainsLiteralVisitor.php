<?php

namespace Psalm\Internal\TypeVisitor;

use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\NodeVisitor;
use Psalm\Type\TypeNode;

class ContainsLiteralVisitor extends NodeVisitor
{
    /**
     * @var bool
     */
    private $contains_literal = false;

    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TLiteralString
            || $type instanceof TLiteralInt
            || $type instanceof TLiteralFloat
            || $type instanceof TTrue
            || $type instanceof TFalse
        ) {
            $this->contains_literal = true;
            return NodeVisitor::STOP_TRAVERSAL;
        }

        if ($type instanceof TArray && $type->type_params[1]->isEmpty()) {
            $this->contains_literal = true;
            return NodeVisitor::STOP_TRAVERSAL;
        }

        return null;
    }

    public function matches(): bool
    {
        return $this->contains_literal;
    }
}
