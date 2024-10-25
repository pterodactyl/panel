<?php

namespace Psalm\Internal\Provider;

use Closure;
use Psalm\CodeLocation;
use Psalm\Plugin\EventHandler\Event\MethodExistenceProviderEvent;
use Psalm\Plugin\EventHandler\MethodExistenceProviderInterface;
use Psalm\Plugin\Hook\MethodExistenceProviderInterface as LegacyMethodExistenceProviderInterface;
use Psalm\StatementsSource;

use function is_subclass_of;
use function strtolower;

class MethodExistenceProvider
{
    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(MethodExistenceProviderEvent): ?bool>
     * >
     */
    private static $handlers = [];

    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(
     *     string,
     *     string,
     *     ?StatementsSource=,
     *     ?CodeLocation
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
     * @param class-string<LegacyMethodExistenceProviderInterface>|class-string<MethodExistenceProviderInterface> $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, LegacyMethodExistenceProviderInterface::class, true)) {
            $callable = Closure::fromCallable([$class, 'doesMethodExist']);

            foreach ($class::getClassLikeNames() as $fq_classlike_name) {
                $this->registerLegacyClosure($fq_classlike_name, $callable);
            }
        } elseif (is_subclass_of($class, MethodExistenceProviderInterface::class, true)) {
            $callable = Closure::fromCallable([$class, 'doesMethodExist']);

            foreach ($class::getClassLikeNames() as $fq_classlike_name) {
                $this->registerClosure($fq_classlike_name, $callable);
            }
        }
    }

    /**
     * @param Closure(MethodExistenceProviderEvent): ?bool $c
     */
    public function registerClosure(string $fq_classlike_name, Closure $c): void
    {
        self::$handlers[strtolower($fq_classlike_name)][] = $c;
    }

    /**
     * @param Closure(
     *     string,
     *     string,
     *     ?StatementsSource=,
     *     ?CodeLocation
     *   ): ?bool $c
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

    public function doesMethodExist(
        string $fq_classlike_name,
        string $method_name_lowercase,
        ?StatementsSource $source = null,
        ?CodeLocation $code_location = null
    ): ?bool {
        foreach (self::$legacy_handlers[strtolower($fq_classlike_name)] ?? [] as $method_handler) {
            $method_exists = $method_handler(
                $fq_classlike_name,
                $method_name_lowercase,
                $source,
                $code_location
            );

            if ($method_exists !== null) {
                return $method_exists;
            }
        }

        foreach (self::$handlers[strtolower($fq_classlike_name)] ?? [] as $method_handler) {
            $event = new MethodExistenceProviderEvent(
                $fq_classlike_name,
                $method_name_lowercase,
                $source,
                $code_location
            );
            $method_exists = $method_handler($event);

            if ($method_exists !== null) {
                return $method_exists;
            }
        }

        return null;
    }
}
