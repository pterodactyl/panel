<?php

namespace Psalm\Internal\Provider;

use Closure;
use Psalm\Plugin\EventHandler\Event\FunctionExistenceProviderEvent;
use Psalm\Plugin\EventHandler\FunctionExistenceProviderInterface;
use Psalm\Plugin\Hook\FunctionExistenceProviderInterface as LegacyFunctionExistenceProviderInterface;
use Psalm\StatementsSource;

use function is_subclass_of;
use function strtolower;

class FunctionExistenceProvider
{
    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(FunctionExistenceProviderEvent): ?bool>
     * >
     */
    private static $handlers = [];

    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(
     *     StatementsSource,
     *     string
     *   ): ?bool>
     * >
     */
    private static $legacy_handlers = [];

    public function __construct()
    {
        self::$handlers = [];
        self::$legacy_handlers = [];
    }

    /**
     * @param class-string $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, LegacyFunctionExistenceProviderInterface::class, true)) {
            $callable = Closure::fromCallable([$class, 'doesFunctionExist']);

            foreach ($class::getFunctionIds() as $function_id) {
                $this->registerLegacyClosure($function_id, $callable);
            }
        } elseif (is_subclass_of($class, FunctionExistenceProviderInterface::class, true)) {
            $callable = Closure::fromCallable([$class, 'doesFunctionExist']);

            foreach ($class::getFunctionIds() as $function_id) {
                $this->registerClosure($function_id, $callable);
            }
        }
    }

    /**
     * @param lowercase-string $function_id
     * @param Closure(FunctionExistenceProviderEvent): ?bool $c
     */
    public function registerClosure(string $function_id, Closure $c): void
    {
        self::$handlers[$function_id][] = $c;
    }

    /**
     * @param lowercase-string $function_id
     * @param Closure(
     *     StatementsSource,
     *     string
     *   ): ?bool $c
     */
    public function registerLegacyClosure(string $function_id, Closure $c): void
    {
        self::$legacy_handlers[$function_id][] = $c;
    }

    public function has(string $function_id): bool
    {
        return isset(self::$handlers[strtolower($function_id)]) ||
            isset(self::$legacy_handlers[strtolower($function_id)]);
    }

    public function doesFunctionExist(
        StatementsSource $statements_source,
        string $function_id
    ): ?bool {
        foreach (self::$legacy_handlers[strtolower($function_id)] ?? [] as $function_handler) {
            $function_exists = $function_handler(
                $statements_source,
                $function_id
            );

            if ($function_exists !== null) {
                return $function_exists;
            }
        }

        foreach (self::$handlers[strtolower($function_id)] ?? [] as $function_handler) {
            $event = new FunctionExistenceProviderEvent(
                $statements_source,
                $function_id
            );
            $function_exists = $function_handler($event);

            if ($function_exists !== null) {
                return $function_exists;
            }
        }

        return null;
    }
}
