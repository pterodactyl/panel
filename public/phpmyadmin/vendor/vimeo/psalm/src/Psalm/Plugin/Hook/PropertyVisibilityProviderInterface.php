<?php

namespace Psalm\Plugin\Hook;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

/** @deprecated going to be removed in Psalm 5 */
interface PropertyVisibilityProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames(): array;

    public static function isPropertyVisible(
        StatementsSource $source,
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        Context $context,
        CodeLocation $code_location
    ): ?bool;
}
