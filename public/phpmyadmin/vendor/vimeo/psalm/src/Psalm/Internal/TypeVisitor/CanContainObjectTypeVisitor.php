<?php

namespace Psalm\Internal\TypeVisitor;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\NodeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

class CanContainObjectTypeVisitor extends NodeVisitor
{
    /**
     * @var bool
     */
    private $contains_object_type = false;

    /**
     * @var Codebase
     */
    private $codebase;

    public function __construct(Codebase $codebase)
    {
        $this->codebase = $codebase;
    }

    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof Union
            && (
                UnionTypeComparator::canBeContainedBy($this->codebase, new Union([new TObject()]), $type)
                && UnionTypeComparator::canBeContainedBy($this->codebase, $type, new Union([new TObject()]))
            )
            ||
            $type instanceof Atomic
            && (
                AtomicTypeComparator::isContainedBy($this->codebase, new TObject(), $type)
                || AtomicTypeComparator::isContainedBy($this->codebase, $type, new TObject())
            )
        ) {
            $this->contains_object_type = true;
            return NodeVisitor::STOP_TRAVERSAL;
        }

        return null;
    }

    public function matches(): bool
    {
        return $this->contains_object_type;
    }
}
