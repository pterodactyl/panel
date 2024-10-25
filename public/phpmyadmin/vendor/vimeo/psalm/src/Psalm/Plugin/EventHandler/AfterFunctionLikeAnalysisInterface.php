<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent;

interface AfterFunctionLikeAnalysisInterface
{
    /**
     * Called after a statement has been checked
     *
     * @return null|false
     */
    public static function afterStatementAnalysis(AfterFunctionLikeAnalysisEvent $event): ?bool;
}
