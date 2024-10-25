<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AfterClassLikeAnalysisEvent;

interface AfterClassLikeAnalysisInterface
{
    /**
     * Called after a statement has been checked
     *
     * @return null|false
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint
     */
    public static function afterStatementAnalysis(AfterClassLikeAnalysisEvent $event);
}
