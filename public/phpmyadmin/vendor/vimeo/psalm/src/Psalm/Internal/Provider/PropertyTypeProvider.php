<?php

namespace Psalm\Internal\Provider;

use Closure;
use Psalm\Context;
use Psalm\Internal\Provider\PropertyTypeProvider\DomDocumentPropertyTypeProvider;
use Psalm\Plugin\EventHandler\Event\PropertyTypeProviderEvent;
use Psalm\Plugin\EventHandler\PropertyTypeProviderInterface;
use Psalm\Plugin\Hook\PropertyTypeProviderInterface as LegacyPropertyTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\Type\Union;

use function is_subclass_of;
use function strtolower;

class PropertyTypeProvider
{
    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(PropertyTypeProviderEvent): ?Union>
     * >
     */
    private static $handlers = [];

    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(
     *     string,
     *     string,
     *     bool,
     *     ?StatementsSource=,
     *     ?Context=
     *   ): ?Union>
     * >
     */
    private static $legacy_handlers = [];

    public function __construct()
    {
        self::$handlers = [];
        self::$legacy_handlers = [];

        $this->registerClass(DomDocumentPropertyTypeProvider::class);
    }

    /**
     * @param class-string $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, LegacyPropertyTypeProviderInterface::class, true)) {
            $callable = Closure::fromCallable([$class, 'getPropertyType']);

            foreach ($class::getClassLikeNames() as $fq_classlike_name) {
                $this->registerLegacyClosure($fq_classlike_name, $callable);
            }
        } elseif (is_subclass_of($class, PropertyTypeProviderInterface::class, true)) {
            $callable = Closure::fromCallable([$class, 'getPropertyType']);

            foreach ($class::getClassLikeNames() as $fq_classlike_name) {
                $this->registerClosure($fq_classlike_name, $callable);
            }
        }
    }

    /**
     * @param Closure(PropertyTypeProviderEvent): ?Union $c
     */
    public function registerClosure(string $fq_classlike_name, Closure $c): void
    {
        self::$handlers[strtolower($fq_classlike_name)][] = $c;
    }

    /**
     * @param Closure(
     *     string,
     *     string,
     *     bool,
     *     ?StatementsSource=,
     *     ?Context=
     *   ): ?Union $c
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

    public function getPropertyType(
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        ?StatementsSource $source = null,
        ?Context $context = null
    ): ?Union {

        if ($source) {
            $source->addSuppressedIssues(['NonInvariantDocblockPropertyType']);
        }

        foreach (self::$legacy_handlers[strtolower($fq_classlike_name)] ?? [] as $property_handler) {
            $property_type = $property_handler(
                $fq_classlike_name,
                $property_name,
                $read_mode,
                $source,
                $context
            );

            if ($property_type !== null) {
                return $property_type;
            }
        }

        foreach (self::$handlers[strtolower($fq_classlike_name)] ?? [] as $property_handler) {
            $event = new PropertyTypeProviderEvent(
                $fq_classlike_name,
                $property_name,
                $read_mode,
                $source,
                $context
            );
            $property_type = $property_handler($event);

            if ($property_type !== null) {
                return $property_type;
            }
        }

        return null;
    }
}
