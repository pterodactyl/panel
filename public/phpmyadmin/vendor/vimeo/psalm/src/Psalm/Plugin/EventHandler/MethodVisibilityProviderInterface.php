<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\MethodVisibilityProviderEvent;

interface MethodVisibilityProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames(): array;

    public static function isMethodVisible(MethodVisibilityProviderEvent $event): ?bool;
}
