<?php

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Expr;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\StatementsSource;

class AfterExpressionAnalysisEvent
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
     * @var FileManipulation[]
     */
    private $file_replacements;

    /**
     * Called after an expression has been checked
     *
     * @param  FileManipulation[]   $file_replacements
     */
    public function __construct(
        Expr $expr,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array $file_replacements = []
    ) {
        $this->expr = $expr;
        $this->context = $context;
        $this->statements_source = $statements_source;
        $this->codebase = $codebase;
        $this->file_replacements = $file_replacements;
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

    /**
     * @return FileManipulation[]
     */
    public function getFileReplacements(): array
    {
        return $this->file_replacements;
    }

    /**
     * @param FileManipulation[] $file_replacements
     */
    public function setFileReplacements(array $file_replacements): void
    {
        $this->file_replacements = $file_replacements;
    }
}
