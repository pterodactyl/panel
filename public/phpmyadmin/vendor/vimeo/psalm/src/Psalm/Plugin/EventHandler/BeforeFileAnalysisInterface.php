<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\BeforeFileAnalysisEvent;

interface BeforeFileAnalysisInterface
{
    /**
     * Called before a file has been checked
     */
    public static function beforeAnalyzeFile(BeforeFileAnalysisEvent $event): void;
}
