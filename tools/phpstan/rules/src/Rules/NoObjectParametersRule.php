<?php

declare(strict_types=1);

namespace Rules\Rules;

use Rules\Support\SignatureResolver;
use Rules\Support\TypeClassifier;
use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Port of anti-slop's no-object-parameters: the bare `object` type on an input
 * carries no property or method evidence. Docblock aliases resolving to
 * `object` are caught for free because the resolver returns combined types.
 *
 * @implements Rule<FunctionLike>
 */
final class NoObjectParametersRule implements Rule
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
        foreach ($signature->parameters as $parameter) {
            if (! $this->typeClassifier->containsBroadObject($parameter->type)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Parameter `$%s` uses the broad `object` type. Accept a named owner type; parse external input (cuyz/valinor, spatie/laravel-data) at its boundary before calling this function.',
                $parameter->name,
            ))
                ->identifier('rules.noObjectParameters')
                ->line($node->getStartLine())
                ->build();
        }

        return $errors;
    }
}
