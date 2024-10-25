<?php

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\CodeLocation;
use Psalm\StatementsSource;

class MethodExistenceProviderEvent
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
     * @var StatementsSource|null
     */
    private $source;
    /**
     * @var CodeLocation|null
     */
    private $code_location;

    /**
     * Use this hook for informing whether or not a method exists on a given object. If you know the method does
     * not exist, return false. If you aren't sure if it exists or not, return null and the default analysis will
     * continue to determine if the method actually exists.
     */
    public function __construct(
        string $fq_classlike_name,
        string $method_name_lowercase,
        ?StatementsSource $source = null,
        ?CodeLocation $code_location = null
    ) {
        $this->fq_classlike_name = $fq_classlike_name;
        $this->method_name_lowercase = $method_name_lowercase;
        $this->source = $source;
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

    public function getSource(): ?StatementsSource
    {
        return $this->source;
    }

    public function getCodeLocation(): ?CodeLocation
    {
        return $this->code_location;
    }
}
