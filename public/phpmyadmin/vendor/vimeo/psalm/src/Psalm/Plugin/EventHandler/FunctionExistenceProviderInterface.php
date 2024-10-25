<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\FunctionExistenceProviderEvent;

interface FunctionExistenceProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array;

    /**
     * Use this hook for informing whether or not a global function exists. If you know the function does
     * not exist, return false. If you aren't sure if it exists or not, return null and the default analysis
     * will continue to determine if the function actually exists.
     *
     */
    public static function doesFunctionExist(FunctionExistenceProviderEvent $event): ?bool;
}
