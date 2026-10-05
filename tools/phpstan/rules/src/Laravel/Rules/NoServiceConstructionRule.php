<?php

declare(strict_types=1);

namespace Rules\Laravel\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Port of anti-slop's Effect no-service-constructor-imports, adapted to
 * Laravel: runtime code must receive services through constructor injection,
 * not build them with `new`, `app()`, `resolve()`, `App::make()`, or
 * `$this->app->make()`. Providers, tests, factories, and the service's own
 * static constructors are the composition roots and are exempt.
 *
 * @implements Rule<CallLike>
 */
final class NoServiceConstructionRule implements Rule
{
    private const array CONTAINER_FUNCTIONS = ['app', 'resolve'];

    /**
     * @param  list<string>  $serviceNamespaces
     * @param  list<string>  $allowedPaths  fnmatch() patterns relative to the project root
     */
    public function __construct(
        private readonly array $serviceNamespaces = ['App\\Services\\', 'App\\Actions\\', 'App\\Repositories\\'],
        private readonly array $allowedPaths = ['app/Providers/**', 'tests/**', 'database/factories/**'],
    ) {}

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $serviceClass = $this->constructedServiceClass($node, $scope);
        if ($serviceClass === null) {
            return [];
        }

        if ($this->isAllowedFile($scope->getFile()) || $this->isOwnClass($serviceClass, $scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Runtime code constructs the service `%s` instead of receiving it. Inject it through the constructor and let the container own the wiring.',
                $serviceClass,
            ))
                ->identifier('rulesLaravel.noServiceConstruction')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function constructedServiceClass(CallLike $node, Scope $scope): ?string
    {
        if ($node instanceof New_ && $node->class instanceof Name) {
            return $this->matchServiceNamespace($node->class->toString());
        }

        if ($node instanceof FuncCall && $node->name instanceof Name
            && in_array($node->name->toLowerString(), self::CONTAINER_FUNCTIONS, true)
        ) {
            return $this->firstArgumentServiceClass($node, $scope);
        }

        if ($node instanceof StaticCall && $node->class instanceof Name
            && $node->name instanceof Identifier && $node->name->toLowerString() === 'make'
        ) {
            $class = $node->class->toString();
            if ($class === 'App' || str_ends_with($class, '\\App') || str_ends_with($class, '\\Facades\\App')) {
                return $this->firstArgumentServiceClass($node, $scope);
            }

            return null;
        }

        if ($node instanceof MethodCall && $node->name instanceof Identifier
            && in_array($node->name->toLowerString(), ['make', 'makewith'], true)
        ) {
            foreach ($scope->getType($node->var)->getObjectClassReflections() as $receiver) {
                if ($receiver->is('Illuminate\Contracts\Container\Container')) {
                    return $this->firstArgumentServiceClass($node, $scope);
                }
            }

            return null;
        }

        return null;
    }

    private function firstArgumentServiceClass(CallLike $node, Scope $scope): ?string
    {
        $arguments = $node->getArgs();
        if ($arguments === []) {
            return null;
        }

        foreach ($scope->getType($arguments[0]->value)->getConstantStrings() as $constantString) {
            $match = $this->matchServiceNamespace($constantString->getValue());
            if ($match !== null) {
                return $match;
            }
        }

        return null;
    }

    private function matchServiceNamespace(string $class): ?string
    {
        foreach ($this->serviceNamespaces as $namespace) {
            if (str_starts_with($class, $namespace)) {
                return $class;
            }
        }

        return null;
    }

    private function isAllowedFile(string $file): bool
    {
        $normalized = str_replace('\\', '/', $file);
        foreach ($this->allowedPaths as $pattern) {
            if (fnmatch('*'.ltrim($pattern, '/'), $normalized)) {
                return true;
            }
        }

        return false;
    }

    private function isOwnClass(string $serviceClass, Scope $scope): bool
    {
        $classReflection = $scope->getClassReflection();

        return $classReflection !== null && $classReflection->getName() === $serviceClass;
    }
}
