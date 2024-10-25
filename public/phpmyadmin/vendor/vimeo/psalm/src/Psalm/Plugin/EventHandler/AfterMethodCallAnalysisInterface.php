<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;

interface AfterMethodCallAnalysisInterface
{
    public static function afterMethodCallAnalysis(AfterMethodCallAnalysisEvent $event): void;
}
