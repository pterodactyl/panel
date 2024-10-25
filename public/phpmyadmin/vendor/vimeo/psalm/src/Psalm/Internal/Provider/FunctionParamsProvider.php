<?php

namespace Psalm\Internal\Provider;

use Closure;
use PhpParser\Node\Arg;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\EventHandler\Event\FunctionParamsProviderEvent;
use Psalm\Plugin\EventHandler\FunctionParamsProviderInterface;
use Psalm\Plugin\Hook\FunctionParamsProviderInterface as LegacyFunctionParamsProviderInterface;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeParameter;

use function is_subclass_of;
use function strtolower;

class FunctionParamsProvider
{
    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(FunctionParamsProviderEvent): ?array<int, FunctionLikeParameter>>
     * >
     */
    private static $handlers = [];

    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(
     *     StatementsSource,
     *     string,
     *     list<Arg>,
     *     ?Context=,
     *     ?CodeLocation=
     *   ): ?array<int, FunctionLikeParameter>>
     * >
     */
    private static $legacy_handlers = [];

    public function __construct()
    {
        self::$handlers = [];
        self::$legacy_handlers = [];
    }

    /**
     * @param class-string<LegacyFunctionParamsProviderInterface>|class-string<FunctionParamsProviderInterface> $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, LegacyFunctionParamsProviderInterface::class, true)) {
            $callable = Closure::fromCallable([$class, 'getFunctionParams']);

            foreach ($class::getFunctionIds() as $function_id) {
                $this->registerLegacyClosure($function_id, $callable);
            }
        } elseif (is_subclass_of($class, FunctionParamsProviderInterface::class, true)) {
            $callable = Closure::fromCallable([$class, 'getFunctionParams']);

            foreach ($class::getFunctionIds() as $function_id) {
                $this->registerClosure($function_id, $callable);
            }
        }
    }

    /**
     * @param Closure(FunctionParamsProviderEvent): ?array<int, FunctionLikeParameter> $c
     */
    public function registerClosure(string $fq_classlike_name, Closure $c): void
    {
        self::$handlers[strtolower($fq_classlike_name)][] = $c;
    }

    /**
     * @param Closure(
     *     StatementsSource,
     *     string,
     *     list<Arg>,
     *     ?Context=,
     *     ?CodeLocation=
     *   ): ?array<int, FunctionLikeParameter> $c
     */
    public function registerLegacyClosure(string $fq_classlike_name, Closure $c): void
    {
        self::$legacy_handlers[strtolower($fq_classlike_name)][] = $c;
    }

    public function has(string $fq_classlike_name): bool
    {
        return isset(self::$handlers[strtolower($fq_classlike_name)]) ||
            isset(self::$legacy_handlers[strtolower($fq_classlike_name)]);
    }

    /**
     * @param list<Arg> $call_args
     *
     * @return  ?array<int, FunctionLikeParameter>
     */
    public function getFunctionParams(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        ?Context $context = null,
        ?CodeLocation $code_location = null
    ): ?array {
        foreach (self::$handlers[strtolower($function_id)] ?? [] as $class_handler) {
            $event = new FunctionParamsProviderEvent(
                $statements_source,
                $function_id,
                $call_args,
                $context,
                $code_location
            );
            $result = $class_handler($event);

            if ($result) {
                return $result;
            }
        }

        foreach (self::$legacy_handlers[strtolower($function_id)] ?? [] as $class_handler) {
            $result = $class_handler(
                $statements_source,
                $function_id,
                $call_args,
                $context,
                $code_location
            );

            if ($result) {
                return $result;
            }
        }

        return null;
    }
}
