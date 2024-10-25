<?php

namespace Psalm\Plugin\Hook;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

/** @deprecated going to be removed in Psalm 5 */
interface MethodVisibilityProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames(): array;

    public static function isMethodVisible(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name_lowercase,
        Context $context,
        ?CodeLocation $code_location = null
    ): ?bool;
}
