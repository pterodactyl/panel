<?php

namespace Psalm\Internal\TypeVisitor;

use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\NodeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

class FromDocblockSetter extends NodeVisitor
{
    /**
     * @psalm-suppress MoreSpecificImplementedParamType
     *
     * @param  Atomic|Union $type
     * @return self::STOP_TRAVERSAL|self::DONT_TRAVERSE_CHILDREN|null
     */
    protected function enterNode(TypeNode $type): ?int
    {
        $type->from_docblock = true;

        if ($type instanceof TTemplateParam
            && $type->as->isMixed()
        ) {
            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }
}
