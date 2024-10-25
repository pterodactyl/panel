<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;

interface RemoveTaintsInterface
{
    /**
     * Called to see what taints should be removed
     *
     * @return list<string>
     */
    public static function removeTaints(AddRemoveTaintsEvent $event): array;
}
