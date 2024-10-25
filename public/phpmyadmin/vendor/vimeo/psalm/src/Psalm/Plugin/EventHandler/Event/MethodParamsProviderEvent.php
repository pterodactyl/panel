<?php

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

class MethodParamsProviderEvent
{
    /**
     * @var string
     */
    private $fq_classlike_name;
    /**
     * @var string
     */
    private $method_name_lowercase;
    /**
     * @var list<PhpParser\Node\Arg>|null
     */
    private $call_args;
    /**
     * @var StatementsSource|null
     */
    private $statements_source;
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
        string $fq_classlike_name,
        string $method_name_lowercase,
        ?array $call_args = null,
        ?StatementsSource $statements_source = null,
        ?Context $context = null,
        ?CodeLocation $code_location = null
    ) {
        $this->fq_classlike_name = $fq_classlike_name;
        $this->method_name_lowercase = $method_name_lowercase;
        $this->call_args = $call_args;
        $this->statements_source = $statements_source;
        $this->context = $context;
        $this->code_location = $code_location;
    }

    public function getFqClasslikeName(): string
    {
        return $this->fq_classlike_name;
    }

    public function getMethodNameLowercase(): string
    {
        return $this->method_name_lowercase;
    }

    /**
     * @return list<PhpParser\Node\Arg>|null
     */
    public function getCallArgs(): ?array
    {
        return $this->call_args;
    }

    public function getStatementsSource(): ?StatementsSource
    {
        return $this->statements_source;
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
