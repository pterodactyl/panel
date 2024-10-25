<?php

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

class FunctionParamsProviderEvent
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
     * @var PhpParser\Node\Arg[]
     */
    private $call_args;
    /**
     * @var Context|null
     */
    private $context;
    /**
     * @var CodeLocation|null
     */
    private $code_location;

    /**
     * @param  list<PhpParser\Node\Arg>    $call_args
     */
    public function __construct(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        ?Context $context = null,
        ?CodeLocation $code_location = null
    ) {
        $this->statements_source = $statements_source;
        $this->function_id = $function_id;
        $this->call_args = $call_args;
        $this->context = $context;
        $this->code_location = $code_location;
    }

    public function getStatementsSource(): StatementsSource
    {
        return $this->statements_source;
    }

    public function getFunctionId(): string
    {
        return $this->function_id;
    }

    /**
     * @return PhpParser\Node\Arg[]
     */
    public function getCallArgs(): array
    {
        return $this->call_args;
    }

    public function getContext(): ?Context
    {
        return $this->context;
    }

    public function getCodeLocation(): ?CodeLocation
    {
        return $this->code_location;
    }
}
