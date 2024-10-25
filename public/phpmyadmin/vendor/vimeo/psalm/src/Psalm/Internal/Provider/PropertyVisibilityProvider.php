<?php

namespace Psalm\Internal\Provider;

use Closure;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\EventHandler\Event\PropertyVisibilityProviderEvent;
use Psalm\Plugin\EventHandler\PropertyVisibilityProviderInterface;
use Psalm\Plugin\Hook\PropertyVisibilityProviderInterface as LegacyPropertyVisibilityProviderInterface;
use Psalm\StatementsSource;

use function is_subclass_of;
use function strtolower;

class PropertyVisibilityProvider
{
    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(PropertyVisibilityProviderEvent): ?bool>
     * >
     */
    private static $handlers = [];

    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(
     *     StatementsSource,
     *     string,
     *     string,
     *     bool,
     *     Context,
     *     CodeLocation
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
     * @param class-string<LegacyPropertyVisibilityProviderInterface>
     *     |class-string<PropertyVisibilityProviderInterface> $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, LegacyPropertyVisibilityProviderInterface::class, true)) {
            $callable = Closure::fromCallable([$class, 'isPropertyVisible']);

            foreach ($class::getClassLikeNames() as $fq_classlike_name) {
                $this->registerLegacyClosure($fq_classlike_name, $callable);
            }
        } elseif (is_subclass_of($class, PropertyVisibilityProviderInterface::class, true)) {
            $callable = Closure::fromCallable([$class, 'isPropertyVisible']);

            foreach ($class::getClassLikeNames() as $fq_classlike_name) {
                $this->registerClosure($fq_classlike_name, $callable);
            }
        }
    }

    /**
     * @param Closure(PropertyVisibilityProviderEvent): ?bool $c
     */
    public function registerClosure(string $fq_classlike_name, Closure $c): void
    {
        self::$handlers[strtolower($fq_classlike_name)][] = $c;
    }

    /**
     * @param Closure(
     *     StatementsSource,
     *     string,
     *     string,
     *     bool,
     *     Context,
     *     CodeLocation
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

    public function isPropertyVisible(
        StatementsSource $source,
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        Context $context,
        CodeLocation $code_location
    ): ?bool {
        foreach (self::$legacy_handlers[strtolower($fq_classlike_name)] ?? [] as $property_handler) {
            $property_visible = $property_handler(
                $source,
                $fq_classlike_name,
                $property_name,
                $read_mode,
                $context,
                $code_location
            );

            if ($property_visible !== null) {
                return $property_visible;
            }
        }

        foreach (self::$handlers[strtolower($fq_classlike_name)] ?? [] as $property_handler) {
            $event = new PropertyVisibilityProviderEvent(
                $source,
                $fq_classlike_name,
                $property_name,
                $read_mode,
                $context,
                $code_location
            );
            $property_visible = $property_handler($event);

            if ($property_visible !== null) {
                return $property_visible;
            }
        }

        return null;
    }
}
