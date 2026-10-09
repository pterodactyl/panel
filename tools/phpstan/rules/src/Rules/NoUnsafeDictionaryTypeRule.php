<?php

declare(strict_types=1);

namespace Rules\Rules;

use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Rules\Support\SignatureResolver;
use Rules\Support\TypeClassifier;

/**
 * Port of anti-slop's no-unsafe-dictionary-type for parameter and return
 * positions: a dictionary contract whose value type is an escape hatch gives
 * callers no usable evidence. PHPStan level 6 already flags bare `array`; this
 * adds the value-is-escape-hatch check on top.
 *
 * @implements Rule<FunctionLike>
 */
final class NoUnsafeDictionaryTypeRule implements Rule
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
        if ($signature === null || $signature->hasAsserts) {
            return [];
        }

        $errors = [];
        foreach ($signature->parameters as $parameter) {
            $kind = $this->typeClassifier->unsafeDictionaryValueKind($parameter->type);
            if ($kind !== null) {
                $errors[] = $this->buildError($node, sprintf(
                    'Parameter `$%s` is a dictionary of `%s` values, which gives callers no value contract. Declare an `array{...}` shape or a readonly DTO; parse external payloads (cuyz/valinor, spatie/laravel-data) before insertion.',
                    $parameter->name,
                    $kind,
                ));
            }
        }

        $returnKind = $this->typeClassifier->unsafeDictionaryValueKind($signature->returnType);
        if ($returnKind !== null) {
            $errors[] = $this->buildError($node, sprintf(
                'This function returns a dictionary of `%s` values, which gives callers no value contract. Declare an `array{...}` shape or a readonly DTO; parse external payloads before returning them.',
                $returnKind,
            ));
        }

        return $errors;
    }

    private function buildError(FunctionLike $node, string $message): \PHPStan\Rules\IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->identifier('rules.noUnsafeDictionaryType')
            ->line($node->getStartLine())
            ->build();
    }
}
