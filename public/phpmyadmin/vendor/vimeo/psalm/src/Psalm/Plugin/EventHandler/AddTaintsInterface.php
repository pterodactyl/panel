<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;

interface AddTaintsInterface
{
    /**
     * Called to see what taints should be added
     *
     * @return list<string>
     */
    public static function addTaints(AddRemoveTaintsEvent $event): array;
}
