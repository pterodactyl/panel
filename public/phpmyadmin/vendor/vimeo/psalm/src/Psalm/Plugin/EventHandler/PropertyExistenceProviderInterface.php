<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\PropertyExistenceProviderEvent;

interface PropertyExistenceProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames(): array;

    /**
     * Use this hook for informing whether or not a property exists on a given object. If you know the property does
     * not exist, return false. If you aren't sure if it exists or not, return null and the default analysis will
     * continue to determine if the property actually exists.
     *
     */
    public static function doesPropertyExist(PropertyExistenceProviderEvent $event): ?bool;
}
