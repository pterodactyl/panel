<?php

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Stmt;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\StatementsSource;

class AfterStatementAnalysisEvent
{
    /**
     * @var Stmt
     */
    private $stmt;
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
     * Called after a statement has been checked
     *
     * @param  FileManipulation[]   $file_replacements
     */
    public function __construct(
        Stmt $stmt,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array $file_replacements = []
    ) {
        $this->stmt = $stmt;
        $this->context = $context;
        $this->statements_source = $statements_source;
        $this->codebase = $codebase;
        $this->file_replacements = $file_replacements;
    }

    public function getStmt(): Stmt
    {
        return $this->stmt;
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
