<?php

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Expr\FuncCall;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\StatementsSource;
use Psalm\Type\Union;

class AfterFunctionCallAnalysisEvent
{
    /**
     * @var FuncCall
     */
    private $expr;
    /**
     * @var non-empty-string
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
    /**
     * @var Union
     */
    private $return_type_candidate;
    /**
     * @var FileManipulation[]
     */
    private $file_replacements;

    /**
     * @param non-empty-string $function_id
     * @param FileManipulation[] $file_replacements
     */
    public function __construct(
        FuncCall $expr,
        string $function_id,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        Union $return_type_candidate,
        array $file_replacements
    ) {
        $this->expr = $expr;
        $this->function_id = $function_id;
        $this->context = $context;
        $this->statements_source = $statements_source;
        $this->codebase = $codebase;
        $this->return_type_candidate = $return_type_candidate;
        $this->file_replacements = $file_replacements;
    }

    public function getExpr(): FuncCall
    {
        return $this->expr;
    }

    /**
     * @return non-empty-string
     */
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

    public function getReturnTypeCandidate(): Union
    {
        return $this->return_type_candidate;
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
