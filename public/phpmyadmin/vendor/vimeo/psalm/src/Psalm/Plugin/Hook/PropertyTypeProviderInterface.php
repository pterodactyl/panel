<?php

namespace Psalm\Plugin\Hook;

use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type\Union;

/** @deprecated going to be removed in Psalm 5 */
interface PropertyTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames(): array;

    public static function getPropertyType(
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        ?StatementsSource $source = null,
        ?Context $context = null
    ): ?Union;
}
