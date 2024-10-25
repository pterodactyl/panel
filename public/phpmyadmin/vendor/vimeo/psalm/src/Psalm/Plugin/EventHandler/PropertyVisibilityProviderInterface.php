<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\PropertyVisibilityProviderEvent;

interface PropertyVisibilityProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames(): array;

    public static function isPropertyVisible(PropertyVisibilityProviderEvent $event): ?bool;
}
