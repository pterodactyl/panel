<?php

namespace Psalm\Plugin\Hook;

use PhpParser\Node\Arg;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeParameter;

/** @deprecated going to be removed in Psalm 5 */
interface FunctionParamsProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array;

    /**
     * @param  list<Arg>    $call_args
     *
     * @return ?array<int, FunctionLikeParameter>
     */
    public static function getFunctionParams(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        ?Context $context = null,
        ?CodeLocation $code_location = null
    ): ?array;
}
