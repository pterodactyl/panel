<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;

interface AfterClassLikeVisitInterface
{
    /**
     * @return void
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint
     */
    public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event);
}
