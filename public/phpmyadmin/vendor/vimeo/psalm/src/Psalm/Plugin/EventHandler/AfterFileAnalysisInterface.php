<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AfterFileAnalysisEvent;

interface AfterFileAnalysisInterface
{
    /**
     * Called after a file has been checked
     */
    public static function afterAnalyzeFile(AfterFileAnalysisEvent $event): void;
}
