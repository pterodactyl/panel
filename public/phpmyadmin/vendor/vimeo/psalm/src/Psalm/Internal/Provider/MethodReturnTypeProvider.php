<?php

namespace Psalm\Internal\Provider;

use Closure;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Provider\ReturnTypeProvider\ClosureFromCallableReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\DateTimeModifyReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\DomNodeAppendChild;
use Psalm\Internal\Provider\ReturnTypeProvider\ImagickPixelColorReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\PdoStatementReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\SimpleXmlElementAsXml;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Plugin\Hook\MethodReturnTypeProviderInterface as LegacyMethodReturnTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\Type\Union;

use function is_subclass_of;
use function strtolower;

class MethodReturnTypeProvider
{
    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(MethodReturnTypeProviderEvent): ?Union>
     * >
     */
    private static $handlers = [];

    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(
     *     StatementsSource,
     *     string,
     *     lowercase-string,
     *     list<PhpParser\Node\Arg>,
     *     Context,
     *     CodeLocation,
     *     ?array<Union>=,
     *     ?string=,
     *     ?lowercase-string=
     *   ): ?Union>
     * >
     */
    private static $legacy_handlers = [];

    public function __construct()
    {
        self::$handlers = [];
        self::$legacy_handlers = [];

        $this->registerClass(DomNodeAppendChild::class);
        $this->registerClass(ImagickPixelColorReturnTypeProvider::class);
        $this->registerClass(SimpleXmlElementAsXml::class);
        $this->registerClass(PdoStatementReturnTypeProvider::class);
        $this->registerClass(ClosureFromCallableReturnTypeProvider::class);
        $this->registerClass(DateTimeModifyReturnTypeProvider::class);
    }

    /**
     * @param class-string $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, LegacyMethodReturnTypeProviderInterface::class, true)) {
            $callable = Closure::fromCallable([$class, 'getMethodReturnType']);

            foreach ($class::getClassLikeNames() as $fq_classlike_name) {
                $this->registerLegacyClosure($fq_classlike_name, $callable);
            }
        } elseif (is_subclass_of($class, MethodReturnTypeProviderInterface::class, true)) {
            $callable = Closure::fromCallable([$class, 'getMethodReturnType']);

            foreach ($class::getClassLikeNames() as $fq_classlike_name) {
                $this->registerClosure($fq_classlike_name, $callable);
            }
        }
    }

    /**
     * @param Closure(MethodReturnTypeProviderEvent): ?Union $c
     */
    public function registerClosure(string $fq_classlike_name, Closure $c): void
    {
        self::$handlers[strtolower($fq_classlike_name)][] = $c;
    }

    /**
     * @param Closure(
     *     StatementsSource,
     *     string,
     *     lowercase-string,
     *     list<PhpParser\Node\Arg>,
     *     Context,
     *     CodeLocation,
     *     ?array<Union>=,
     *     ?string=,
     *     ?lowercase-string=
     *   ): ?Union $c
     *
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
     * @param PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $stmt
     * @param  ?array<Union> $template_type_parameters
     */
    public function getReturnType(
        StatementsSource $statements_source,
        string $fq_classlike_name,
        string $method_name,
        $stmt,
        Context $context,
        CodeLocation $code_location,
        ?array $template_type_parameters = null,
        ?string $called_fq_classlike_name = null,
        ?string $called_method_name = null
    ): ?Union {
        foreach (self::$legacy_handlers[strtolower($fq_classlike_name)] ?? [] as $class_handler) {
            $result = $class_handler(
                $statements_source,
                $fq_classlike_name,
                strtolower($method_name),
                $stmt->getArgs(),
                $context,
                $code_location,
                $template_type_parameters,
                $called_fq_classlike_name,
                $called_method_name ? strtolower($called_method_name) : null
            );

            if ($result) {
                return $result;
            }
        }

        foreach (self::$handlers[strtolower($fq_classlike_name)] ?? [] as $class_handler) {
            $event = new MethodReturnTypeProviderEvent(
                $statements_source,
                $fq_classlike_name,
                strtolower($method_name),
                $stmt,
                $context,
                $code_location,
                $template_type_parameters,
                $called_fq_classlike_name,
                $called_method_name ? strtolower($called_method_name) : null
            );
            $result = $class_handler($event);

            if ($result) {
                return $result;
            }
        }

        return null;
    }
}
