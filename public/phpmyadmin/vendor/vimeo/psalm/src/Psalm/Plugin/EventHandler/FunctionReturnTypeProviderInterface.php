<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Type\Union;

interface FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array;

    /**
     * Use this hook for providing custom return type logic. If this plugin does not know what a function should
     * return but another plugin may be able to determine the type, return null. Otherwise return a mixed union type
     * if something should be returned, but can't be more specific.
     */
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union;
}
