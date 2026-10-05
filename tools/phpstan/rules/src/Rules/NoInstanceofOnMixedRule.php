<?php

declare(strict_types=1);

namespace Rules\Rules;

use Rules\Support\TypeClassifier;
use PhpParser\Node;
use PhpParser\Node\Expr\Instanceof_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * The instanceof half of anti-slop's no-runtime-typeof: `$value instanceof T`
 * where `$value` is `mixed` narrows a representation that was never parsed.
 * Functions carrying `@phpstan-assert` tags are boundary parsers and exempt,
 * as are files under the configured boundary paths.
 *
 * @implements Rule<Instanceof_>
 */
final class NoInstanceofOnMixedRule implements Rule
{
    /**
     * @param  list<string>  $boundaryPaths  fnmatch() patterns relative to the project root
     */
    public function __construct(
        private readonly TypeClassifier $typeClassifier,
        private readonly array $boundaryPaths = [],
    ) {}

    public function getNodeType(): string
    {
        return Instanceof_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->typeClassifier->isExplicitMixed($scope->getType($node->expr))) {
            return [];
        }

        if ($this->isBoundaryFunction($scope) || $this->isBoundaryFile($scope->getFile())) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'This `instanceof` narrows a `mixed` value without establishing its contract. Parse the value at its I/O boundary (cuyz/valinor, spatie/laravel-data), then branch on the domain type.',
            )
                ->identifier('rules.noRuntimeTypeChecks')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function isBoundaryFunction(Scope $scope): bool
    {
        $function = $scope->getFunction();

        return $function !== null && $function->getAsserts()->getAll() !== [];
    }

    private function isBoundaryFile(string $file): bool
    {
        $normalized = str_replace('\\', '/', $file);
        foreach ($this->boundaryPaths as $pattern) {
            if (fnmatch('*'.ltrim($pattern, '/'), $normalized)) {
                return true;
            }
        }

        return false;
    }
}
