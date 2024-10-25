<?php

namespace Psalm\Plugin\Hook;

use PhpParser\Node\Arg;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeParameter;

/** @deprecated going to be removed in Psalm 5 */
interface MethodParamsProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames(): array;

    /**
     * @param  list<Arg> $call_args
     *
     * @return ?array<int, FunctionLikeParameter>
     */
    public static function getMethodParams(
        string $fq_classlike_name,
        string $method_name_lowercase,
        ?array $call_args = null,
        ?StatementsSource $statements_source = null,
        ?Context $context = null,
        ?CodeLocation $code_location = null
    ): ?array;
}
