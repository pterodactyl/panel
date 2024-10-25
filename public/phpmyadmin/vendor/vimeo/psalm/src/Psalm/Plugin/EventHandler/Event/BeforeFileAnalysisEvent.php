<?php

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Codebase;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;

class BeforeFileAnalysisEvent
{
    /**
     * @var StatementsSource
     */
    private $statements_source;
    /**
     * @var Context
     */
    private $file_context;
    /**
     * @var FileStorage
     */
    private $file_storage;
    /**
     * @var Codebase
     */
    private $codebase;

    /**
     * Called before a file has been checked
     */
    public function __construct(
        StatementsSource $statements_source,
        Context $file_context,
        FileStorage $file_storage,
        Codebase $codebase
    ) {
        $this->statements_source = $statements_source;
        $this->file_context = $file_context;
        $this->file_storage = $file_storage;
        $this->codebase = $codebase;
    }

    public function getStatementsSource(): StatementsSource
    {
        return $this->statements_source;
    }

    public function getFileContext(): Context
    {
        return $this->file_context;
    }

    public function getFileStorage(): FileStorage
    {
        return $this->file_storage;
    }

    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }
}
