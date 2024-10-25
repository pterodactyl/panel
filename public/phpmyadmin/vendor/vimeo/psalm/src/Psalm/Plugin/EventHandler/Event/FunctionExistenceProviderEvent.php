<?php

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\StatementsSource;

class FunctionExistenceProviderEvent
{
    /**
     * @var StatementsSource
     */
    private $statements_source;
    /**
     * @var string
     */
    private $function_id;

    /**
     * Use this hook for informing whether or not a global function exists. If you know the function does
     * not exist, return false. If you aren't sure if it exists or not, return null and the default analysis
     * will continue to determine if the function actually exists.
     */
    public function __construct(
        StatementsSource $statements_source,
        string $function_id
    ) {
        $this->statements_source = $statements_source;
        $this->function_id = $function_id;
    }

    public function getStatementsSource(): StatementsSource
    {
        return $this->statements_source;
    }

    public function getFunctionId(): string
    {
        return $this->function_id;
    }
}
