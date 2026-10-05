<?php

declare(strict_types=1);

namespace Rules\Support;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ClosureType;

/**
 * Resolves any function-like node into a ResolvedSignature. Methods and named
 * functions come from reflection so docblock types and `@phpstan-assert` tags
 * are honored; closures and arrow functions come from the scope's inferred
 * ClosureType, which has no assert metadata.
 */
final class SignatureResolver
{
    public function __construct(private readonly ReflectionProvider $reflectionProvider) {}

    public function resolve(FunctionLike $node, Scope $scope): ?ResolvedSignature
    {
        if ($node instanceof ClassMethod) {
            return $this->resolveMethod($node, $scope);
        }

        if ($node instanceof Function_) {
            return $this->resolveFunction($node, $scope);
        }

        if ($node instanceof Closure || $node instanceof ArrowFunction) {
            return $this->resolveClosure($node, $scope);
        }

        return null;
    }

    private function resolveMethod(ClassMethod $node, Scope $scope): ?ResolvedSignature
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $method = $classReflection->getNativeMethod($node->name->toString());
        $variant = $method->getOnlyVariant();

        return $this->buildSignature(
            $variant->getParameters(),
            $variant->getReturnType(),
            $method->getAsserts(),
            sprintf('%s::%s()', $classReflection->getDisplayName(), $node->name->toString()),
        );
    }

    private function resolveFunction(Function_ $node, Scope $scope): ?ResolvedSignature
    {
        $name = $node->namespacedName ?? $node->name;
        // SAFETY: php-parser Name nodes stringify to their qualified form.
        $nameNode = new Node\Name\FullyQualified((string) $name);
        if (! $this->reflectionProvider->hasFunction($nameNode, $scope)) {
            return null;
        }

        $function = $this->reflectionProvider->getFunction($nameNode, $scope);
        $variant = $function->getOnlyVariant();

        return $this->buildSignature(
            $variant->getParameters(),
            $variant->getReturnType(),
            $function->getAsserts(),
            $function->getName().'()',
        );
    }

    private function resolveClosure(Closure|ArrowFunction $node, Scope $scope): ?ResolvedSignature
    {
        $type = $scope->getType($node);
        if (! $type instanceof ClosureType) {
            return null;
        }

        $parameters = [];
        foreach ($type->getParameters() as $parameter) {
            $parameters[] = new ResolvedParameter($parameter->getName(), $parameter->getType(), false);
        }

        return new ResolvedSignature(
            $parameters,
            $type->getReturnType(),
            false,
            $node instanceof ArrowFunction ? '{arrow function}' : '{closure}',
        );
    }

    /**
     * @param  list<\PHPStan\Reflection\ParameterReflection>  $parameters
     */
    private function buildSignature(
        array $parameters,
        \PHPStan\Type\Type $returnType,
        Assertions $assertions,
        string $describedName,
    ): ResolvedSignature {
        $assertedParameterNames = [];
        foreach ($assertions->getAll() as $assertTag) {
            // describe() is the covered API; it prints the "$name" form.
            $assertedParameterNames[ltrim($assertTag->getParameter()->describe(), '$')] = true;
        }

        $resolved = [];
        foreach ($parameters as $parameter) {
            $resolved[] = new ResolvedParameter(
                $parameter->getName(),
                $parameter->getType(),
                isset($assertedParameterNames[$parameter->getName()]),
            );
        }

        return new ResolvedSignature($resolved, $returnType, $assertedParameterNames !== [], $describedName);
    }
}
