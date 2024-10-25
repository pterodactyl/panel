<?php

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Context;
use Psalm\StatementsSource;

class PropertyTypeProviderEvent
{
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
     * @var StatementsSource|null
     */
    private $source;
    /**
     * @var Context|null
     */
    private $context;

    public function __construct(
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        ?StatementsSource $source = null,
        ?Context $context = null
    ) {
        $this->fq_classlike_name = $fq_classlike_name;
        $this->property_name = $property_name;
        $this->read_mode = $read_mode;
        $this->source = $source;
        $this->context = $context;
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

    public function getSource(): ?StatementsSource
    {
        return $this->source;
    }

    public function getContext(): ?Context
    {
        return $this->context;
    }
}
