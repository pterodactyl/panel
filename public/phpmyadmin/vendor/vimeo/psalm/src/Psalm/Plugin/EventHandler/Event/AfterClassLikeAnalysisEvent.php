<?php

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node;
use Psalm\Codebase;
use Psalm\FileManipulation;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;

class AfterClassLikeAnalysisEvent
{
    /**
     * @var Node\Stmt\ClassLike
     */
    private $stmt;
    /**
     * @var ClassLikeStorage
     */
    private $classlike_storage;
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
        Node\Stmt\ClassLike $stmt,
        ClassLikeStorage $classlike_storage,
        StatementsSource $statements_source,
        Codebase $codebase,
        array $file_replacements = []
    ) {
        $this->stmt = $stmt;
        $this->classlike_storage = $classlike_storage;
        $this->statements_source = $statements_source;
        $this->codebase = $codebase;
        $this->file_replacements = $file_replacements;
    }

    public function getStmt(): Node\Stmt\ClassLike
    {
        return $this->stmt;
    }

    public function getClasslikeStorage(): ClassLikeStorage
    {
        return $this->classlike_storage;
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
