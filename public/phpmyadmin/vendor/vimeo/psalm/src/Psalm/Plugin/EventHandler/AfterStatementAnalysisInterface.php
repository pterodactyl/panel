<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AfterStatementAnalysisEvent;

interface AfterStatementAnalysisInterface
{
    /**
     * Called after a statement has been checked
     *
     * @return null|false
     */
    public static function afterStatementAnalysis(AfterStatementAnalysisEvent $event): ?bool;
}
