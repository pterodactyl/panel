<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AfterClassLikeExistenceCheckEvent;

interface AfterClassLikeExistenceCheckInterface
{
    public static function afterClassLikeExistenceCheck(AfterClassLikeExistenceCheckEvent $event): void;
}
