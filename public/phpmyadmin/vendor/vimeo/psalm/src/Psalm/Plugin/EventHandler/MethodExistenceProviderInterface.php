<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\MethodExistenceProviderEvent;

interface MethodExistenceProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames(): array;

    /**
     * Use this hook for informing whether or not a method exists on a given object. If you know the method does
     * not exist, return false. If you aren't sure if it exists or not, return null and the default analysis will
     * continue to determine if the method actually exists.
     */
    public static function doesMethodExist(MethodExistenceProviderEvent $event): ?bool;
}
