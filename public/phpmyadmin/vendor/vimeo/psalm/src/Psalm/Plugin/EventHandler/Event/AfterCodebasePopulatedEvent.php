<?php

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Codebase;

class AfterCodebasePopulatedEvent
{
    /**
     * @var Codebase
     */
    private $codebase;

    /**
     * Called after codebase has been populated
     */
    public function __construct(Codebase $codebase)
    {
        $this->codebase = $codebase;
    }

    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }
}
