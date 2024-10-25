<?php

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

class MethodVisibilityProviderEvent
{
    /**
     * @var StatementsSource
     */
    private $source;
    /**
     * @var string
     */
    private $fq_classlike_name;
    /**
     * @var string
     */
    private $method_name_lowercase;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var CodeLocation|null
     */
    private $code_location;

    public function __construct(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name_lowercase,
        Context $context,
        ?CodeLocation $code_location = null
    ) {
        $this->source = $source;
        $this->fq_classlike_name = $fq_classlike_name;
        $this->method_name_lowercase = $method_name_lowercase;
        $this->context = $context;
        $this->code_location = $code_location;
    }

    public function getSource(): StatementsSource
    {
        return $this->source;
    }

    public function getFqClasslikeName(): string
    {
        return $this->fq_classlike_name;
    }

    public function getMethodNameLowercase(): string
    {
        return $this->method_name_lowercase;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getCodeLocation(): ?CodeLocation
    {
        return $this->code_location;
    }
}
