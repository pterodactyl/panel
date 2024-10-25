<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\FunctionParamsProviderEvent;
use Psalm\Storage\FunctionLikeParameter;

interface FunctionParamsProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array;

    /**
     * @return ?array<int, FunctionLikeParameter>
     */
    public static function getFunctionParams(FunctionParamsProviderEvent $event): ?array;
}
