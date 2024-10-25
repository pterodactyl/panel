<?php

namespace Psalm\Plugin\EventHandler\Event;

class StringInterpreterEvent
{
    /**
     * @var string
     */
    private $value;

    /**
     * Called after a statement has been checked
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
