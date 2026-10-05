<?php

declare(strict_types=1);

namespace Rules\Rules;

use Rules\Support\SignatureResolver;
use Rules\Support\TypeClassifier;
use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;

/**
 * Port of anti-slop's no-unknown-parameters: a parameter declared `mixed`
 * (natively or via `@param`) leaves its input unparsed. The exemption is a
 * function that carries a `@phpstan-assert` family tag for that parameter,
 * because that function is itself the boundary parser. Implementations are
 * also exempt when a parent or interface already requires mixed at the same
 * parameter position; the owned declaration remains the place to fix.
 *
 * @implements Rule<FunctionLike>
 */
final class NoMixedParametersRule implements Rule
{
    public function __construct(
        private readonly SignatureResolver $signatureResolver,
        private readonly TypeClassifier $typeClassifier,
    ) {}

    public function getNodeType(): string
    {
        return FunctionLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $signature = $this->signatureResolver->resolve($node, $scope);
        if ($signature === null) {
            return [];
        }

        $errors = [];
        foreach ($signature->parameters as $index => $parameter) {
            if ($parameter->hasAssertTag || ! $this->typeClassifier->isExplicitMixed($parameter->type)) {
                continue;
            }

            if ($node instanceof ClassMethod && $this->ancestorRequiresMixed($node, $scope, $index)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Parameter `$%s` leaves input unparsed. Accept a named domain type; run the expected schema or parser (cuyz/valinor, spatie/laravel-data) at the I/O boundary before calling this function.',
                $parameter->name,
            ))
                ->identifier('rules.noMixedParameters')
                ->line($node->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function ancestorRequiresMixed(ClassMethod $node, Scope $scope, int $parameterIndex): bool
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return false;
        }

        foreach ($classReflection->getAncestors() as $ancestor) {
            if ($ancestor->getName() === $classReflection->getName() || ! $ancestor->hasNativeMethod($node->name->toString())) {
                continue;
            }

            foreach ($ancestor->getNativeMethod($node->name->toString())->getVariants() as $variant) {
                $ancestorParameter = $variant->getParameters()[$parameterIndex] ?? null;
                if ($ancestorParameter !== null && $ancestorParameter->getType() instanceof MixedType) {
                    return true;
                }
            }
        }

        return false;
    }
}
