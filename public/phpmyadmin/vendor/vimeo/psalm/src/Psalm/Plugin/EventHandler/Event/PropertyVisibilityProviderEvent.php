<?php

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

class PropertyVisibilityProviderEvent
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
    private $property_name;
    /**
     * @var bool
     */
    private $read_mode;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var CodeLocation
     */
    private $code_location;

    public function __construct(
        StatementsSource $source,
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        Context $context,
        CodeLocation $code_location
    ) {
        $this->source = $source;
        $this->fq_classlike_name = $fq_classlike_name;
        $this->property_name = $property_name;
        $this->read_mode = $read_mode;
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

    public function getPropertyName(): string
    {
        return $this->property_name;
    }

    public function isReadMode(): bool
    {
        return $this->read_mode;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getCodeLocation(): CodeLocation
    {
        return $this->code_location;
    }
}
