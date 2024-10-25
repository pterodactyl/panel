<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\MethodParamsProviderEvent;
use Psalm\Storage\FunctionLikeParameter;

interface MethodParamsProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames(): array;

    /**
     * @return ?array<int, FunctionLikeParameter>
     */
    public static function getMethodParams(MethodParamsProviderEvent $event): ?array;
}
