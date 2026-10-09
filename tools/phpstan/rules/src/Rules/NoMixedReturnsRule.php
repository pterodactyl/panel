<?php

declare(strict_types=1);

namespace Rules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Rules\Support\SignatureResolver;
use Rules\Support\TypeClassifier;

/**
 * Port of anti-slop's no-unknown-returns: a declared return contract of
 * `mixed` — including `Generator<..., mixed>` and `iterable<mixed>` yields —
 * pushes the parsing burden onto every caller. `never` is allowed. Magic
 * methods (`__get`, `__call`, `__callStatic`, `offsetGet`) are allowed because
 * PHP defines their contracts as dynamic; that decision is documented in the
 * README.
 *
 * @implements Rule<FunctionLike>
 */
final class NoMixedReturnsRule implements Rule
{
    private const array ALLOWED_MAGIC_METHODS = ['__get', '__call', '__callStatic', 'offsetGet'];

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
        if ($node instanceof ClassMethod && in_array($node->name->toString(), self::ALLOWED_MAGIC_METHODS, true)) {
            return [];
        }

        // Scope-inferred closure types replace the declared return with the
        // body's inferred type, so the declared contract is read from the AST.
        if ($node instanceof Closure || $node instanceof ArrowFunction) {
            $declared = $node->getReturnType();

            return $declared instanceof Identifier && $declared->toLowerString() === 'mixed'
                ? [$this->buildError($node, 'This function exposes `mixed` to its caller. Parse the value at its boundary (cuyz/valinor, spatie/laravel-data) and return a named domain type.')]
                : [];
        }

        $signature = $this->signatureResolver->resolve($node, $scope);
        if ($signature === null || $signature->hasAsserts) {
            return [];
        }

        $returnType = $signature->returnType;
        if ($this->typeClassifier->isNever($returnType)) {
            return [];
        }

        if ($this->typeClassifier->isExplicitMixed($returnType)) {
            return [$this->buildError($node, 'This function exposes `mixed` to its caller. Parse the value at its boundary (cuyz/valinor, spatie/laravel-data) and return a named domain type.')];
        }

        if ($returnType->isIterable()->yes() && $this->typeClassifier->isExplicitMixed($returnType->getIterableValueType())) {
            return [$this->buildError($node, 'This function yields `mixed` values to its caller. Parse each value at its boundary and yield a named domain type.')];
        }

        return [];
    }

    private function buildError(FunctionLike $node, string $message): \PHPStan\Rules\IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->identifier('rules.noMixedReturns')
            ->line($node->getStartLine())
            ->build();
    }
}
