<?php

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Expr;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\StatementsSource;

class AddRemoveTaintsEvent
{
    /**
     * @var Expr
     */
    private $expr;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var StatementsSource
     */
    private $statements_source;
    /**
     * @var Codebase
     */
    private $codebase;

    /**
     * Called after an expression has been checked
     */
    public function __construct(
        Expr $expr,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase
    ) {
        $this->expr = $expr;
        $this->context = $context;
        $this->statements_source = $statements_source;
        $this->codebase = $codebase;
    }

    public function getExpr(): Expr
    {
        return $this->expr;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getStatementsSource(): StatementsSource
    {
        return $this->statements_source;
    }

    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }
}
