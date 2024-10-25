<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Type\Union;

interface MethodReturnTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames(): array;

    /**
     * Use this hook for providing custom return type logic. If this plugin does not know what a method should return
     * but another plugin may be able to determine the type, return null. Otherwise return a mixed union type if
     * something should be returned, but can't be more specific.
     */
    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union;
}
