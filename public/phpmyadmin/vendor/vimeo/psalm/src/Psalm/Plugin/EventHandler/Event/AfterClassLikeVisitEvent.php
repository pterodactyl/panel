<?php

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Stmt\ClassLike;
use Psalm\Codebase;
use Psalm\FileManipulation;
use Psalm\FileSource;
use Psalm\Storage\ClassLikeStorage;

class AfterClassLikeVisitEvent
{
    /**
     * @var ClassLike
     */
    private $stmt;
    /**
     * @var ClassLikeStorage
     */
    private $storage;
    /**
     * @var FileSource
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
     * @param  FileManipulation[] $file_replacements
     */
    public function __construct(
        ClassLike $stmt,
        ClassLikeStorage $storage,
        FileSource $statements_source,
        Codebase $codebase,
        array $file_replacements = []
    ) {
        $this->stmt = $stmt;
        $this->storage = $storage;
        $this->statements_source = $statements_source;
        $this->codebase = $codebase;
        $this->file_replacements = $file_replacements;
    }

    public function getStmt(): ClassLike
    {
        return $this->stmt;
    }

    public function getStorage(): ClassLikeStorage
    {
        return $this->storage;
    }

    public function getStatementsSource(): FileSource
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
