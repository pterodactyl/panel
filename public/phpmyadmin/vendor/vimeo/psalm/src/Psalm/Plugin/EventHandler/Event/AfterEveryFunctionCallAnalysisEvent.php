<?php

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Expr\FuncCall;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\StatementsSource;

class AfterEveryFunctionCallAnalysisEvent
{
    /**
     * @var FuncCall
     */
    private $expr;
    /**
     * @var string
     */
    private $function_id;
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

    public function __construct(
        FuncCall $expr,
        string $function_id,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase
    ) {
        $this->expr = $expr;
        $this->function_id = $function_id;
        $this->context = $context;
        $this->statements_source = $statements_source;
        $this->codebase = $codebase;
    }

    public function getExpr(): FuncCall
    {
        return $this->expr;
    }

    public function getFunctionId(): string
    {
        return $this->function_id;
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
